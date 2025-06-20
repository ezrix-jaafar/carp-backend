<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carpet;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BulkCarpetController extends Controller
{
    /**
     * Generate multiple carpet records in bulk for an order
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateBulkCarpets(Request $request, Order $order)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:50',
            'carpet_type_id' => 'nullable|exists:carpet_types,id',
            'replace' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quantity = $request->input('quantity');
        $carpetTypeId = $request->input('carpet_type_id'); // Now optional
        
        $replace = $request->boolean('replace', false);
        // Delete existing carpets if replace requested
        if ($replace) {
            $order->carpets()->delete();
            $currentCount = 0;
        } else {
            // Get current number of carpets
            $currentCount = $order->carpets()->count();
        }
        
        // Create multiple carpet records
        $carpets = [];
        for ($i = 1; $i <= $quantity; $i++) {
            $carpetNumber = $currentCount + $i;
            $qrCode = Carpet::generateQrCode($order->id, $carpetNumber);
            // Use the current count plus the total quantity being added for the denominator
            // For pack number we need denominator = currentCount + quantity when not replacing, otherwise quantity
            $packTotal = $replace ? $quantity : ($currentCount + $quantity);
            $packNumber = ($currentCount + $i) . '/' . $packTotal;
            
            // Create the base carpet record, carpet_type_id can be null
            $carpetData = [
                'qr_code' => $qrCode,
                'pack_number' => $packNumber,
                'color' => 'Unknown',
                'status' => 'pending',
                'additional_charges' => 0,
            ];
            
            // Only add carpet_type_id if it's provided
            if ($carpetTypeId) {
                $carpetData['carpet_type_id'] = $carpetTypeId;
            }
            
            $carpet = $order->carpets()->create($carpetData);
            
            $carpets[] = $carpet;
        }
        
        // Update the order total_carpets field
        $order->update([
            'total_carpets' => $currentCount + $quantity
        ]);
        
        return response()->json([
            'message' => 'Carpet labels generated successfully',
            'carpets' => $carpets
        ], 201);
    }
    
    /**
     * Get carpet details using QR code
     *
     * @param string $qrCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCarpetByQrCode($qrCode)
    {
        $carpet = Carpet::with(['carpetType', 'order.client.user'])
            ->where('qr_code', $qrCode)
            ->first();
            
        if (!$carpet) {
            return response()->json(['message' => 'Carpet not found'], 404);
        }
        
        return response()->json($carpet);
    }
    
    /**
     * Update carpet details using QR code
     *
     * @param Request $request
     * @param string $qrCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCarpetByQrCode(Request $request, $qrCode)
    {
        $carpet = Carpet::where('qr_code', $qrCode)->first();
        
        if (!$carpet) {
            return response()->json(['message' => 'Carpet not found'], 404);
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'width' => 'nullable|numeric|min:0.1',
            'length' => 'nullable|numeric|min:0.1',
            'color' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,picked_up,in_cleaning,cleaned,delivered',
            'notes' => 'nullable|string',
            'additional_charges' => 'nullable|numeric|min:0',
            'carpet_type_id' => 'nullable|exists:carpet_types,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Update carpet details
        $carpet->update($request->only([
            'width', 'length', 'color', 'status', 'notes', 'additional_charges', 'carpet_type_id'
        ]));
        
        return response()->json([
            'message' => 'Carpet updated successfully',
            'carpet' => $carpet
        ]);
    }
}
