<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Student: {{ $student->first_name }} {{ $student->last_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="{{ old('email', $student->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Student ID</label>
                            <input type="text" name="student_id" value="{{ old('student_id', $student->student_id) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Photo</label>
                            @if($student->photo)
                                <div class="mt-2 mb-2 flex items-center gap-4">
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->first_name }}" class="w-16 h-16 rounded-full object-cover flex-shrink-0">
                                    <button type="button" onclick="deletePhoto({{ $student->id }})" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 border border-red-300 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors cursor-pointer touch-manipulation">
                                        Delete Photo
                                    </button>
                                </div>
                            @endif
                            <input type="file" name="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select...</option>
                                <option value="male" {{ $student->gender == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ $student->gender == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ $student->gender == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Grade</label>
                            <select name="academic_grade_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Select a grade...</option>@foreach($grades ?? [] as $grade)<option value="{{ $grade->id }}" {{ old('academic_grade_id', $student->academic_grade_id) == $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>@endforeach</select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Enrollment Status *</label>
                            <select name="enrollment_status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="active" {{ $student->enrollment_status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="pending" {{ $student->enrollment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="inactive" {{ $student->enrollment_status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="graduated" {{ $student->enrollment_status == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                <option value="withdrawn" {{ $student->enrollment_status == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Enrollment Date</label>
                            <input type="date" name="enrollment_date" value="{{ old('enrollment_date', $student->enrollment_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="tel" name="phone" value="{{ old('phone', $student->phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" name="address" value="{{ old('address', $student->address) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="city" value="{{ old('city', $student->city) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Province</label>
                            <input type="text" name="province" value="{{ old('province', $student->province) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $student->postal_code) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Medical Notes</label>
                        <textarea name="medical_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('medical_notes', $student->medical_notes) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $student->notes) }}</textarea>
                    </div>

                    <!-- Form buttons - Update inside this form -->
                    <div class="flex items-center justify-between pt-4 border-t">
                        <div class="flex items-center gap-4">
                            <a href="{{ route('students.show', $student) }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Update Student</button>
                        </div>
                    </div>
                </form>

                <!-- Delete form - completely separate from update form -->
                <div class="px-6 pb-6">
                    <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Are you sure you want to delete this student? This cannot be undone.')">
                        @csrf 
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">🗑️ Delete Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deletePhoto(studentId) {
            if (!confirm('Are you sure you want to delete this photo?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("/students") }}/' + studentId + '/photo';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';

            form.appendChild(csrfInput);
            form.appendChild(methodInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-app-layout>
