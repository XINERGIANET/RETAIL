# Plan de Implementación: Facturación con Pagos Anticipados (SUNAT)

> **Origen:** adaptación del plan ya implementado en `XINERGIAPRO` (`C:\Users\Acer\Documents\Xpande\Pro\XINERGIAPRO\plan_anticipos_sunat.md`).
>
> **Estado en RETAIL:** ⏳ Fase 2 — depende de que [`facturacion_electronica_saas.md`](./facturacion_electronica_saas.md) esté implementado primero (necesita `ApisunatService` y los campos `electronic_invoice_*` en `movements`).

Este documento detalla la arquitectura y lógica necesaria para implementar un flujo de anticipos que cumpla con los requerimientos técnicos del sistema y las normativas de la SUNAT.

## 1. Reglas de Negocio y SUNAT (Obligatorio)
Según la normativa de SUNAT (UBL 2.1), el manejo de anticipos no es simplemente "restar dinero". El flujo legal exige:
1. **El Anticipo genera un comprobante:** cuando se recibe el dinero por adelantado, **se debe emitir una Boleta o Factura** por el monto del anticipo (con un ítem o glosa "Anticipo" o similar).
2. **La Venta Final genera el comprobante total:** al entregar el producto, se emite una Factura/Boleta por el **Monto Total** de la venta, detallando los productos reales.
3. **Referencia Cruzada:** el comprobante final debe referenciar el comprobante del anticipo (Serie y Número) y restar ese monto del Total a Pagar.

## 2. Modificaciones en la Base de Datos

En lugar de un simple campo nulo, usar una tabla intermedia que conecte el comprobante final con los comprobantes de anticipo, ya que una venta podría pagarse con más de un anticipo.

**Nueva Tabla:** `sale_advances`
*   `id`: Primary Key
*   `final_movement_id`: ID del Movimiento de la venta final.
*   `advance_movement_id`: ID del Movimiento del anticipo original (para referenciar la boleta/factura en SUNAT).
*   `applied_amount`: Monto del anticipo que se está descontando (Decimal).
*   `created_at`, `updated_at`

**Campo adicional:** `is_advance` (booleano) en `sales_movements` (RETAIL ya tiene esta tabla con `series`/`billing_status` — agregar la columna ahí) para identificar fácilmente si esa venta fue solo la emisión de un anticipo.

> **Nota RETAIL:** en RETAIL el equivalente al flujo de "pedido que se factura después" es `SaleOrder` (`app/Models/SaleOrder.php`, con `movement_id` que enlaza al `Movement` final — ver `sale_orders.movement_id` ya implementado). Los anticipos sobre un `SaleOrder` deben registrarse igual como un `Movement` propio y luego vincularse en `sale_advances` contra el `movement_id` que termine generando ese pedido al facturarse.

## 3. Lógica del Sistema (Backend / Controladores)

### Fase A: Emisión del Anticipo
1. Crear una vista/módulo donde se cobre un "Anticipo" (puede integrarse al flujo de pago de `SaleOrderController::addPayment` o como una acción independiente).
2. Se genera un `Movement` y `SalesMovement` como una venta normal, afectando la caja positivamente.
3. Se envía a Apisunat generando una Boleta/Factura normal cuyo detalle es "Pago Anticipado".
4. Se marca esta venta como un anticipo disponible para el cliente (`is_advance = true`).

### Fase B: Venta Final y Aplicación del Anticipo
1. En el POS (`SalesController@processSale`) o al facturar un Pedido (`SaleOrderController@invoice`), al seleccionar al cliente, el sistema busca si tiene "Anticipos Disponibles" (movimientos `is_advance=true` no aplicados aún en `sale_advances`).
2. Si el cajero decide aplicar el anticipo, se envían en el Request los IDs de los anticipos a usar.
3. **Cálculo Financiero:**
   * `Subtotal / IGV / Total`: se calculan sobre el precio real de los productos (ej. Total = 100).
   * `Monto a Pagar (Cobro en Caja)`: Total Venta − Anticipo Aplicado (ej. 100 − 30 = 70).
4. Se guardan los registros en `sale_advances` vinculando la venta final con la(s) venta(s) del anticipo.

## 4. Cambios en la Facturación Electrónica (`ApisunatService.php`)

Para que SUNAT valide la resta del anticipo en la factura final, modificar `buildDocumentBody` para incluir los siguientes nodos UBL 2.1:

1. **`cac:AdditionalDocumentReference`** — indica qué comprobantes previos se están descontando:
   ```xml
   <cac:AdditionalDocumentReference>
       <cbc:ID>F001-00000001</cbc:ID> <!-- Serie y número del anticipo -->
       <cbc:DocumentTypeCode listID="01">02</cbc:DocumentTypeCode> <!-- 02 = Anticipo Factura, 03 = Anticipo Boleta -->
       <cbc:DocumentStatusCode listID="Anticipo">1</cbc:DocumentStatusCode>
   </cac:AdditionalDocumentReference>
   ```

2. **`cac:PrepaidPayment`** (por cada anticipo):
   - `cbc:ID` con `@schemeID` = tipo doc. anticipo (cat. 12: 02 factura / 03 boleta) y texto `F001-00000001`.
   - `cbc:PaidAmount` = **valor de venta** del anticipo (sin IGV), no el total con IGV.
   - `cbc:InstructionID` con `@schemeID="6"` = RUC del emisor.

3. **`cac:AllowanceCharge`** (obligatorio si hay `PrepaidAmount` — error SUNAT 3287): descuento global por anticipo (catálogo 53 código **02**, afecta base IGV).
   ```xml
   <cac:AllowanceCharge>
       <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
       <cbc:AllowanceChargeReasonCode>02</cbc:AllowanceChargeReasonCode>
       <cbc:Amount currencyID="PEN">25.42</cbc:Amount>
       <cbc:BaseAmount currencyID="PEN">84.75</cbc:BaseAmount>
   </cac:AllowanceCharge>
   ```
   El `Amount` debe coincidir con la suma de `PaidAmount` de los `PrepaidPayment`.

4. **Ajuste en `cac:LegalMonetaryTotal`** (orden UBL: `PrepaidAmount` antes de `PayableAmount`):
   ```xml
   <cac:LegalMonetaryTotal>
       <cbc:LineExtensionAmount currencyID="PEN">59.33</cbc:LineExtensionAmount>
       <cbc:TaxInclusiveAmount currencyID="PEN">100.00</cbc:TaxInclusiveAmount>
       <cbc:PrepaidAmount currencyID="PEN">25.42</cbc:PrepaidAmount>
       <cbc:PayableAmount currencyID="PEN">70.00</cbc:PayableAmount>
   </cac:LegalMonetaryTotal>
   ```
   - `PrepaidAmount` = suma de valores de venta de anticipos (sin IGV).
   - `PayableAmount` = total factura − total anticipos **con IGV**.

## 5. Cambios en la Interfaz de Usuario (Frontend)

1. **En el POS (Ventas) y en Pedidos de Venta:** al buscar/seleccionar un cliente (por RUC o DNI), mostrar una alerta: *"Este cliente tiene S/ X.00 en anticipos a su favor"*.
2. **Modal de Cobro:** agregar sección "Aplicar Anticipo". Si el Total es S/ 100 y se aplican S/ 30 de anticipo, el "Faltante a cobrar" baja automáticamente a S/ 70.
3. **Ticket/PDF de Venta:** mostrar el resumen claro:
   * Total Venta: S/ 100.00
   * Anticipos Aplicados: − S/ 30.00
   * Total Pagado Hoy: S/ 70.00
