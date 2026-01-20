<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <!-- Hero Section -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome to Attendance System</h1>
                    <p class="text-lg text-gray-600">Choose an option below to get started</p>
                </div>

                <!-- Main Options Grid -->
                @php
                    $user = auth()->user();
                    $isAttendanceTakerOnly = $user && $user->hasRole('Teacher') && empty($user->getPrimaryStudentIds());
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 px-4">
                    <!-- Attendance Grid -->
                    <a href="{{ route('attendance.index') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-indigo-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Attendance</h3>
                            <p class="text-sm text-gray-600">Mark daily attendance for classes</p>
                        </div>
                    </a>

                    @if(!$isAttendanceTakerOnly)
                    <!-- Reports -->
                    <a href="{{ route('reports.attendance') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-blue-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Reports</h3>
                            <p class="text-sm text-gray-600">View attendance reports and analytics</p>
                        </div>
                    </a>

                    <!-- Statistics -->
                    <a href="{{ route('reports.attendance.stats') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-purple-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Statistics</h3>
                            <p class="text-sm text-gray-600">Detailed attendance statistics</p>
                        </div>
                    </a>

                    <!-- Classes -->
                    <a href="{{ route('classes.index') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-orange-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Classes</h3>
                            <p class="text-sm text-gray-600">Manage classes and sessions</p>
                        </div>
                    </a>

                    <!-- Students -->
                    <a href="{{ route('students.index') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-pink-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-pink-400 to-pink-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Students</h3>
                            <p class="text-sm text-gray-600">View and manage student profiles</p>
                        </div>
                    </a>

                    <!-- Import -->
                    <a href="{{ route('attendance.import') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-teal-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 1 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Import</h3>
                            <p class="text-sm text-gray-600">Bulk import attendance data</p>
                        </div>
                    </a>
                    @endif

                    <!-- Messages -->
                    @can('view messages')
                    <a href="{{ route('messages.index') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-yellow-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Messages</h3>
                            <p class="text-sm text-gray-600">Send and view messages</p>
                        </div>
                    </a>
                    @endcan

                    <!-- Users (Admin only) -->
                    @can('manage users')
                    <a href="{{ route('users.index') }}" class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all p-8 border-2 border-transparent hover:border-red-500">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Users</h3>
                            <p class="text-sm text-gray-600">Manage system users</p>
                        </div>
                    </a>
                    @endcan
                </div>

                <!-- Quick Stats (Optional) -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6 px-4">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Today's Classes</p>
                                <p class="text-2xl font-bold text-gray-900">{{ \App\Models\SchoolClass::active()->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Students</p>
                                <p class="text-2xl font-bold text-gray-900">{{ \App\Models\Student::where('enrollment_status', 'active')->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">This Week</p>
                                <p class="text-2xl font-bold text-gray-900">{{ now()->format('M j') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
