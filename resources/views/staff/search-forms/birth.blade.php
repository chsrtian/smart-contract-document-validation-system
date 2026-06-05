<form id="birth-search-form" class="space-y-6" data-document-type="birth_certificate">
    @csrf
    <input type="hidden" name="document_type" value="birth_certificate">
    
    <div class="space-y-6">
        <!-- Search Form Section -->
        <div class="space-y-6">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h4 class="font-semibold text-blue-900 mb-2">Birth Record Retrieval Form</h4>
                <p class="text-sm text-blue-700">Fill out the verification slip details to search for birth certificates</p>
            </div>

            <!-- Child Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-baby mr-2 text-blue-600"></i>
                    Name of Child
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="child_last_name" data-search="true" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter last name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="child_first_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" name="child_middle_name" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter middle name (if any)">
                    </div>
                </div>
            </div>

            <!-- Birth Details -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                    Birth Information
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" name="date_of_birth" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sex *</label>
                        <select name="sex" data-search="true"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                            <option value="">Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Place of Birth *</label>
                    <textarea name="place_of_birth" data-search="true" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Hospital/Clinic/Institution/House No., Street, Barangay, City/Municipality, Province"
                              required></textarea>
                </div>
            </div>

            <!-- Parents Information -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-purple-600"></i>
                    Parents Information
                </h5>
                
                <!-- Mother's Information -->
                <div class="mb-6">
                    <h6 class="font-medium text-gray-800 mb-3">Name of Mother</h6>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="mother_last_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Mother's last name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" name="mother_first_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Mother's first name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" name="mother_middle_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Mother's middle name">
                        </div>
                    </div>
                </div>

                <!-- Father's Information -->
                <div>
                    <h6 class="font-medium text-gray-800 mb-3">Name of Father</h6>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="father_last_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Father's last name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" name="father_first_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Father's first name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" name="father_middle_name" data-search="true"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Father's middle name">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Actions -->
            <div class="flex space-x-4">
                <button type="button" id="clear-form" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Clear Form
                </button>
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Search Documents
                </button>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div id="live-preview" class="bg-white rounded-lg border p-4">
            <div class="ss-no-results">
                <i class="fas fa-search"></i>
                <p>Start typing to see matching documents</p>
            </div>
        </div>
    </div>
</form>

{{-- Script removed: All search JS is now handled by /js/staff-search.js --}}