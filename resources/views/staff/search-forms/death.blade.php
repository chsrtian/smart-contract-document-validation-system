<form id="death-search-form" class="space-y-6" data-document-type="death_certificate">
    @csrf
    <input type="hidden" name="document_type" value="death_certificate">
    
    <div class="space-y-6">
        <!-- Search Form Section -->
        <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-2">Death Record Retrieval Form</h4>
                <p class="text-sm text-gray-700">Fill out the verification slip details to search for death certificates</p>
            </div>

            <!-- Deceased Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-gray-600"></i>
                    Name of Deceased *
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="deceased_last_name" data-search="true" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               placeholder="Enter last name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="deceased_first_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" name="deceased_middle_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               placeholder="Enter middle name (if any)">
                    </div>
                </div>
            </div>

            <!-- Death Details -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cross mr-2 text-red-600"></i>
                    Death Information
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Death *</label>
                        <input type="date" name="date_of_death" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age at Death</label>
                        <input type="number" name="age_at_death" data-search="true" min="0" max="150"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               placeholder="Enter age">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sex</label>
                    <select name="sex" data-search="true"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent">
                        <option value="">Select Sex</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Place of Death *</label>
                    <textarea name="place_of_death" data-search="true" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                              placeholder="Institution Name / Full Address (House number, Street, Barangay, City/Municipality, Province)"
                              required></textarea>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                    Additional Details
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cause of Death</label>
                        <input type="text" name="cause_of_death" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                               placeholder="Enter cause of death">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                        <select name="civil_status" data-search="true"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent">
                            <option value="">Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Search Actions -->
            <div class="flex space-x-4">
                <button type="button" id="clear-form-death" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear Form
                </button>
                <button type="submit" 
                        class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Search Documents
                </button>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div id="live-preview-death" class="bg-white rounded-lg border p-4">
            <div class="ss-no-results">
                <i class="fas fa-search"></i>
                <p>Start typing to see matching documents</p>
            </div>
        </div>
    </div>
</form>

{{-- Script removed: All search JS is now handled by /js/staff-search.js --}}