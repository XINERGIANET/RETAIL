<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwitchBranchController extends Controller
{
    public function switch(Request $request, Branch $branch)
    {
        $user = $request->user();
        $personId = $user?->person_id;

        $isPrimaryBranch = $user->person?->branch_id === $branch->id;
        $hasRoleInBranch = DB::table('role_person')
            ->where('person_id', $personId)
            ->where('branch_id', $branch->id)
            ->exists();

        abort_unless($isPrimaryBranch || $hasRoleInBranch, 403, 'No tienes acceso a esta sucursal.');

        $request->session()->put('branch_id', $branch->id);
        $request->session()->put('company_id', $branch->company_id);
        $request->session()->forget(['shift_id', 'shift_snapshot']);

        $shifts = Shift::where('branch_id', $branch->id)->get();
        $currentTime = now()->format('H:i:s');
        $assignedShift = $shifts->filter(
            fn($s) => $currentTime >= $s->start_time && $currentTime <= $s->end_time
        )->first() ?? ($shifts->count() === 1 ? $shifts->first() : null);

        if ($assignedShift) {
            $request->session()->put('shift_id', $assignedShift->id);
            $request->session()->put('shift_snapshot', [
                'name'       => $assignedShift->name,
                'start_time' => $assignedShift->start_time,
                'end_time'   => $assignedShift->end_time,
            ]);
        }

        $referer = $request->headers->get('referer');
        if ($referer && str_starts_with($referer, $request->getSchemeAndHttpHost())) {
            return redirect()->to($referer);
        }

        return redirect()->route('dashboard');
    }
}
