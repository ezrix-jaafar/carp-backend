<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionTypeController extends Controller
{
    /**
     * Display a listing of the commission types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CommissionType::query();
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Filter for default commission type
        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }
        
        $commissionTypes = $query->orderBy('name', 'asc')->paginate(15);
        
        return response()->json($commissionTypes);
    }

    /**
     * Display the specified commission type.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $commissionType = CommissionType::findOrFail($id);
        
        return response()->json($commissionType);
    }
}
