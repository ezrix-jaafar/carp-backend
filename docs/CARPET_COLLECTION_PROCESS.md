# Carpet Collection Process for Agents

## Overview

This document outlines the simplified carpet collection process for agents. The new workflow is designed to make the agent's job during pickup more efficient by focusing only on the essential tasks. The process now includes a bulk label generation feature that allows pre-printing multiple carpet labels before an agent's visit.

## Carpet Label Generation Options

### Option 1: Bulk Pre-generation of Labels (New)

1. **Generate Multiple Labels at Once**
   - HQ staff can pre-generate multiple carpet labels in bulk before agent pickup
   - This creates placeholder carpet records with sequential QR codes and pack numbers
   - Carpet type is NOT required at this stage - it will be filled in when scanning

2. **Update Records via QR Code Scanning**
   - Agents scan pre-printed QR codes during pickup
   - They select the carpet type and update the placeholder records with carpet details and photos
   - This workflow eliminates the need to create records one-by-one in the field

### Option 2: Individual Creation During Pickup

1. **Create Basic Carpet Record**
   - Create carpet records individually during pickup
   - System will automatically assign a Pack number in the format "1/3", "2/3", "3/3", etc.
   - This indicates the carpet's sequence within this specific collection

2. **Take Photos**
   - Capture images of each carpet during pickup
   - Photos will be used by HQ during inspection for reference

3. **Print and Attach Pack Labels**
   - Print QR code labels showing the Pack number
   - Labels must be attached to each carpet

## Required and Optional Fields

**Required Fields:**
- Carpet Type (select from dropdown)
- Width (ft)
- Length (ft)
- Photo (at least one photo of the carpet)

**Optional Fields:**
- Addons (can be selected if needed)
- Notes (for special client requests)
- Additional details will be filled by HQ during inspection

## Special Instructions

- Agents can record client special requests in the notes field
- All detailed measurements and specifications will be handled by HQ staff during inspection
- The new "HQ Inspection" status has been added to the order workflow to facilitate this process

## Workflow Changes

The updated carpet processing workflow now includes:

1. **Pre-Label Generation** (Optional) - HQ generates and prints multiple carpet labels in bulk
2. **Carpet Registration** - Basic details added to an order (either in advance or during pickup)
3. **Pickup** - Agent collects carpets, scans/creates records, takes photos, attaches labels
4. **HQ Inspection** - HQ staff inspects carpets, enters accurate measurements and details
5. **Cleaning** - Carpets undergo cleaning processes
6. **Delivery** - Cleaned carpets are delivered back to clients
7. **Completion** - Order is marked as complete

This workflow ensures that agents can quickly process carpet collections without needing to take precise measurements in the field. The new bulk label generation option further streamlines the process by allowing preparation of all necessary labels in advance.

## Using the Bulk Label Generator

1. **Access the Order Details**
   - Navigate to the order details page in the admin panel
   - Locate the Carpets relation manager section

2. **Generate Labels**
   - Click the "Generate Labels in Bulk" button
   - Enter the number of carpets to generate
   - Carpet type selection is optional (can be left blank)
   - Choose whether to generate a printable PDF

3. **Print and Distribute**
   - Print the generated labels page
   - Cut out individual labels
   - Provide labels to agents before pickup appointments

4. **Mobile App Integration**
   - Agents can scan the QR codes using the mobile app
   - The app will fetch the associated placeholder record
   - Agents can then update with actual measurements and photos
