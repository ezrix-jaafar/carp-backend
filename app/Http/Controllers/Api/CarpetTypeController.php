<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarpetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarpetTypeController extends Controller
{
    /**
     * Display a listing of the carpet types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CarpetType::query();
        
        // Filter by pricing model
        if ($request->has('pricing_model')) {
            $query->where('pricing_model', $request->pricing_model);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        $carpetTypes = $query->orderBy('name', 'asc')->get();
        
        return response()->json([
            'data' => $carpetTypes,
        ]);
    }

    /**
     * Display the specified carpet type.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $carpetType = CarpetType::findOrFail($id);
        
        return response()->json([
            'data' => $carpetType,
        ]);
    }
}
