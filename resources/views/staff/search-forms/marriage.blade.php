<form id="marriage-search-form" class="space-y-6" data-document-type="marriage_certificate">
    @csrf
    <input type="hidden" name="document_type" value="marriage_certificate">
    
    <div class="space-y-6">
        <!-- Search Form Section -->
        <div class="space-y-6">
            <div class="bg-pink-50 p-4 rounded-lg border border-pink-200">
                <h4 class="font-semibold text-pink-900 mb-2">Marriage Record Retrieval Form</h4>
                <p class="text-sm text-pink-700">Fill out the verification slip details to search for marriage certificates</p>
            </div>

            <!-- Husband Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-blue-600"></i>
                    Name of Husband *
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="husband_last_name" data-search="true" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter last name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="husband_first_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" name="husband_middle_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter middle name (if any)">
                    </div>
                </div>
            </div>

            <!-- Wife Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-pink-600"></i>
                    Name of Wife *
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="wife_last_name" data-search="true" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter last name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="wife_first_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" name="wife_middle_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               placeholder="Enter middle name (if any)">
                    </div>
                </div>
            </div>

            <!-- Marriage Details -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-heart mr-2 text-pink-600"></i>
                    Marriage Information
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Marriage *</label>
                        <input type="date" name="date_of_marriage" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Marriage Type</label>
                        <select name="marriage_type" data-search="true"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="Office of">Office of</option>
                            <option value="House of">House of</option>
                            <option value="Barangay of">Barangay of</option>
                            <option value="Church of">Church of</option>
                            <option value="Mosque of">Mosque of</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Place of Marriage *</label>
                    <textarea name="place_of_marriage" data-search="true" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                              placeholder="City/Municipality, Province"
                              required></textarea>
                </div>
            </div>

            <!-- Search Actions -->
            <div class="flex space-x-4">
                <button type="button" id="clear-form-marriage" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-pink-500">
                    Clear Form
                </button>
                <button type="submit" 
                        class="flex-1 bg-pink-600 text-white px-4 py-2 rounded-md hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500">
                    Search Documents
                </button>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div id="live-preview-marriage" class="bg-white rounded-lg border p-4">
            <div class="ss-no-results">
                <i class="fas fa-search"></i>
                <p>Start typing to see matching documents</p>
            </div>
        </div>
    </div>
</form>

{{-- Script removed: All search JS is now handled by /js/staff-search.js --}}