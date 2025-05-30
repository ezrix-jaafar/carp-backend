<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddonServiceController extends Controller
{
    /**
     * Display a listing of the addon services.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = AddonService::query();
        
        // Filter by pricing model
        if ($request->has('pricing_model')) {
            $query->where('pricing_model', $request->pricing_model);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        $addonServices = $query->orderBy('name', 'asc')->get();
        
        return response()->json([
            'data' => $addonServices,
        ]);
    }

    /**
     * Display the specified addon service.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $addonService = AddonService::findOrFail($id);
        
        return response()->json([
            'data' => $addonService,
        ]);
    }
}
