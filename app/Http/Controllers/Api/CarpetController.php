<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carpet;
use App\Models\Image;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CarpetController extends Controller
{
    /**
     * List carpets for operations team with optional status filter & pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        try {
            $statusFilter = $request->input('status'); // array or string
            $perPage = $request->integer('per_page', 15);
            $sortBy = $request->input('sort_by', 'updated_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            $query = Carpet::with(['order.client', 'order.agent']);
            if ($statusFilter) {
                if (is_array($statusFilter)) {
                    $query->whereIn('status', $statusFilter);
                } else {
                    $query->where('status', $statusFilter);
                }
            }

            $carpets = $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
            return response()->json($carpets);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve carpets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get carpets assigned to the authenticated agent filtered by status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assigned(Request $request)
    {
        try {
            $user = $request->user();
            if ($user->role !== 'agent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only agents can access assigned carpets',
                ], 403);
            }

            $statusFilter = $request->input('status'); // can be array or single value
            $perPage = $request->integer('per_page', 15);
            $sortBy = $request->input('sort_by', 'updated_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            $agentId = optional($user->agent)->id;
            if (!$agentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Agent profile not found for this user',
                ], 404);
            }

            $query = Carpet::with(['order.client'])
                ->whereHas('order', function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                });

            if ($statusFilter) {
                if (is_array($statusFilter)) {
                    $query->whereIn('status', $statusFilter);
                } else {
                    $query->where('status', $statusFilter);
                }
            }

            $carpets = $query->orderBy($sortBy, $sortDirection)->paginate($perPage);

            return response()->json($carpets);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve assigned carpets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get all carpets for a specific order.
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $orderId)
    {
        try {
            $user = $request->user();
            $order = Order::with('client', 'agent')->findOrFail($orderId);
            
            // Check if user has permission to view carpets for this order
            if ($user->role === 'client' && $user->client && $user->client->id !== $order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view carpets for this order',
                ], 403);
            }
            
            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view carpets for this order',
                ], 403);
            }
            
            $carpets = Carpet::with('images')
                ->where('order_id', $orderId)
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $carpets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve carpets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific carpet.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $carpet = Carpet::with(['order.client', 'order.agent', 'images'])->findOrFail($id);
            
            // Check if user has permission to view this carpet
            if ($user->role === 'client' && 
                $user->client && 
                $user->client->id !== $carpet->order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this carpet',
                ], 403);
            }
            
            if ($user->role === 'agent' && 
                $user->agent && 
                $user->agent->id !== $carpet->order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this carpet',
                ], 403);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $carpet,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve carpet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a new carpet.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'carpet_type_id' => 'nullable|exists:carpet_types,id',
                'width' => 'nullable|numeric|min:0',
                'length' => 'nullable|numeric|min:0',
                'color' => 'nullable|string',
                'status' => 'nullable|in:pending,picked_up,in_cleaning,cleaned,delivered',
                'notes' => 'nullable|string',
                'additional_charges' => 'nullable|numeric|min:0',
                'images.*' => 'nullable|file|image|max:10240', // Max 10MB
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $user = $request->user();
            
            // Get validated order
            $order = Order::findOrFail($request->order_id);
            
            // Check if the user has permission to add carpets to this order
            if ($user->role === 'client' && 
                $user->client && 
                $user->client->id !== $order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to add carpets to this order',
                ], 403);
            }
            
            if ($user->role === 'agent' && 
                $user->agent && 
                $user->agent->id !== $order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to add carpets to this order',
                ], 403);
            }
            
            // Calculate total carpets and Pack number
            $totalCarpets = $order->carpets()->count() + 1;
            $carpetNumber = $totalCarpets;
            $packNumber = $carpetNumber . '/' . $totalCarpets;
            
            // Generate QR code
            $qrCode = Carpet::generateQrCode($order->id, $carpetNumber);
            
            // Create the carpet with direct width and length fields in feet
            $carpet = Carpet::create([
                'order_id' => $order->id,
                'qr_code' => $qrCode,
                'pack_number' => $packNumber,
                'carpet_type_id' => $request->carpet_type_id,
                'width' => $request->width ?? 0,
                'length' => $request->length ?? 0,
                'color' => $request->color,
                'status' => $request->status ?? 'pending',
                'notes' => $request->notes,
                'additional_charges' => $request->additional_charges ?? 0,
            ]);
            
            // Handle image uploads
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('carpets/' . $carpet->id, 'r2');
                    
                    $image = Image::create([
                        'carpet_id' => $carpet->id,
                        'filename' => $path,
                        'disk' => 'r2',
                        'size' => $imageFile->getSize(),
                        'mime_type' => $imageFile->getMimeType(),
                        'uploaded_by' => $user->id,
                    ]);
                    
                    $uploadedImages[] = $image;
                }
            }
            
            // Update order's total_carpets count
            $totalCarpets = Carpet::where('order_id', $order->id)->count();
            $order->update(['total_carpets' => $totalCarpets]);
            
            // Load relationships
            $carpet->load('images');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Carpet created successfully',
                'data' => $carpet,
                'uploaded_images' => $uploadedImages,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create carpet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update a carpet.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'nullable|in:wool,synthetic,silk,cotton,other',
                'dimensions' => 'nullable|array',
                'dimensions.width' => 'required_with:dimensions|numeric|min:0.1',
                'dimensions.length' => 'required_with:dimensions|numeric|min:0.1',
                'color' => 'nullable|string',
                'status' => 'nullable|in:pending,picked_up,in_cleaning,cleaned,delivered',
                'notes' => 'nullable|string',
                'additional_charges' => 'nullable|numeric|min:0',
                'images.*' => 'nullable|file|image|max:10240', // Max 10MB
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $user = $request->user();
            $carpet = Carpet::with('order')->findOrFail($id);
            
            // Check if user has permission to update this carpet
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                if ($user->role === 'agent' && (!$user->agent || $user->agent->id !== $carpet->order->agent_id)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to update this carpet',
                    ], 403);
                }
                
                if ($user->role === 'client') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Clients cannot update carpets',
                    ], 403);
                }
            }
            
            // Update dimensions and calculate area if provided
            if ($request->has('dimensions')) {
                $dimensions = $request->dimensions;
                $dimensions['area'] = round($dimensions['width'] * $dimensions['length'], 2);
                $request->merge(['dimensions' => $dimensions]);
            }
            
            // Update the carpet
            $carpet->update($request->only([
                'type', 'dimensions', 'color', 'status', 'notes', 'additional_charges'
            ]));
            
            // Handle image uploads
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('carpets/' . $carpet->id, 'r2');
                    
                    $image = Image::create([
                        'carpet_id' => $carpet->id,
                        'filename' => $path,
                        'disk' => 'r2',
                        'size' => $imageFile->getSize(),
                        'mime_type' => $imageFile->getMimeType(),
                        'uploaded_by' => $user->id,
                    ]);
                    
                    $uploadedImages[] = $image;
                }
            }
            
            // Load relationships
            $carpet->load('images');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Carpet updated successfully',
                'data' => $carpet,
                'uploaded_images' => $uploadedImages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update carpet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a carpet.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only admins can delete carpets
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete carpets',
                ], 403);
            }
            
            $carpet = Carpet::with(['order', 'images'])->findOrFail($id);
            
            // Check if carpet can be deleted
            if ($carpet->order->status !== 'pending' && $carpet->order->status !== 'assigned') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete carpets from orders that are already in process',
                ], 422);
            }
            
            // Delete associated images from storage
            foreach ($carpet->images as $image) {
                if ($image->disk && $image->filename) {
                    Storage::disk($image->disk)->delete($image->filename);
                }
                $image->delete();
            }
            
            // Delete the carpet
            $carpet->delete();
            
            // Update order's total_carpets count
            $totalCarpets = Carpet::where('order_id', $carpet->order_id)->count();
            $carpet->order->update(['total_carpets' => $totalCarpets]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Carpet deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete carpet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Upload images for a carpet.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
        /**
     * Alias for backward compatibility with singular route.
     * Reuses uploadImages logic.
     */
    public function uploadImages(Request $request, $id)
    {
        // Backward compatibility: call the singular handler
        return $this->uploadImage($request, $id);
    }

    public function uploadImage(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'images' => 'required|array',
                'images.*' => 'required|file|image|max:10240', // Max 10MB
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $user = $request->user();
            $carpet = Carpet::findOrFail($id);
            
            // Check if user has permission to upload images
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                if ($user->role === 'agent' && (!$user->agent || $user->agent->id !== $carpet->order->agent_id)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to upload images for this carpet',
                    ], 403);
                }
                
                if ($user->role === 'client') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Clients cannot upload carpet images',
                    ], 403);
                }
            }
            
            // Handle image uploads
            $uploadedImages = [];
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('carpets/' . $carpet->id, 'r2');
                
                $image = Image::create([
                    'carpet_id' => $carpet->id,
                    'filename' => $path,
                    'disk' => 'r2',
                    'size' => $imageFile->getSize(),
                    'mime_type' => $imageFile->getMimeType(),
                    'uploaded_by' => $user->id,
                ]);
                
                $uploadedImages[] = $image;
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload images',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete an image.
     *
     * @param Request $request
     * @param int $id
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request, $id, $imageId)
    {
        try {
            $user = $request->user();
            $carpet = Carpet::findOrFail($id);
            $image = Image::where('carpet_id', $id)->findOrFail($imageId);
            
            // Check if user has permission to delete images
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                if ($user->role === 'agent' && (!$user->agent || $user->agent->id !== $carpet->order->agent_id)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to delete images for this carpet',
                    ], 403);
                }
                
                if ($user->role === 'client') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Clients cannot delete carpet images',
                    ], 403);
                }
            }
            
            // Delete image from storage
            if ($image->disk && $image->filename) {
                Storage::disk($image->disk)->delete($image->filename);
            }
            
            // Delete the image record
            $image->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get all images for a carpet.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImages(Request $request, $id)
    {
        try {
            $user = $request->user();
            $carpet = Carpet::with('order')->findOrFail($id);
            
            // Check if user has permission to view images
            if ($user->role === 'client' && 
                $user->client && 
                $user->client->id !== $carpet->order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view images for this carpet',
                ], 403);
            }
            
            if ($user->role === 'agent' && 
                $user->agent && 
                $user->agent->id !== $carpet->order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view images for this carpet',
                ], 403);
            }
            
            $images = Image::where('carpet_id', $id)
                ->with('uploader')
                ->get()
                ->map(function ($image) {
                    $image->url = $image->getFullUrl();
                    return $image;
                });
            
            return response()->json([
                'status' => 'success',
                'data' => $images,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve images',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Assign an addon service to a carpet.
     *
     * @param Request $request
     * @param int $carpetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignAddonService(Request $request, $carpetId)
    {
        try {
            $user = $request->user();
            $carpet = Carpet::with('order')->findOrFail($carpetId);
            
            // Check if user has permission to assign addon services
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot assign addon services',
                ], 403);
            }
            
            if ($user->role === 'agent' && 
                $user->agent && 
                $user->agent->id !== $carpet->order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to modify this carpet',
                ], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'addon_service_id' => 'required|exists:addon_services,id',
                'price_override' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Check if the addon service is already assigned
            $existing = $carpet->addonServices()
                ->where('addon_service_id', $request->addon_service_id)
                ->first();
            
            if ($existing) {
                // Update the pivot data
                $carpet->addonServices()->updateExistingPivot(
                    $request->addon_service_id,
                    [
                        'price_override' => $request->price_override,
                        'notes' => $request->notes,
                    ]
                );
                
                $message = 'Addon service assignment updated successfully';
            } else {
                // Attach the addon service
                $carpet->addonServices()->attach(
                    $request->addon_service_id,
                    [
                        'price_override' => $request->price_override,
                        'notes' => $request->notes,
                    ]
                );
                
                $message = 'Addon service assigned successfully';
            }
            
            // Get the updated pivot data
            $assignment = $carpet->addonServices()
                ->where('addon_service_id', $request->addon_service_id)
                ->first()
                ->pivot;
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $assignment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign addon service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
