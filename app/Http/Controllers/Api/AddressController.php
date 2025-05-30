<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Display a listing of the client's addresses.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $addresses = $client->addresses()->orderBy('is_default', 'desc')->get();
        
        return response()->json([
            'data' => $addresses,
        ]);
    }

    /**
     * Store a newly created address in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $address = new Address($request->all());
        $address->client_id = $client->id;
        $address->save();
        
        // If this is set as default, ensure it's properly set
        if ($request->is_default) {
            $address->setAsDefault();
        }
        
        return response()->json([
            'message' => 'Address created successfully',
            'data' => $address,
        ], 201);
    }

    /**
     * Display the specified address.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $address = $client->addresses()->findOrFail($id);
        
        return response()->json([
            'data' => $address,
        ]);
    }

    /**
     * Update the specified address in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $address = $client->addresses()->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'label' => 'string|max:255',
            'address_line_1' => 'string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'string|max:255',
            'state' => 'string|max:255',
            'postal_code' => 'string|max:255',
            'is_default' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $address->update($request->all());
        
        // If this is set as default, ensure it's properly set
        if ($request->has('is_default') && $request->is_default) {
            $address->setAsDefault();
        }
        
        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address,
        ]);
    }

    /**
     * Remove the specified address from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $address = $client->addresses()->findOrFail($id);
        
        // Check if this is the only address
        if ($client->addresses()->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the only address',
            ], 400);
        }
        
        // If deleting a default address, set another one as default
        if ($address->is_default) {
            $newDefault = $client->addresses()->where('id', '!=', $id)->first();
            if ($newDefault) {
                $newDefault->setAsDefault();
            }
        }
        
        $address->delete();
        
        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }
    
    /**
     * Set an address as the default for a client.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setDefault($id)
    {
        $client = Auth::user()->client;
        
        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 404);
        }
        
        $address = $client->addresses()->findOrFail($id);
        $address->setAsDefault();
        
        return response()->json([
            'message' => 'Default address updated successfully',
            'data' => $address,
        ]);
    }
}
