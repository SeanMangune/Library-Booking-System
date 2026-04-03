<div x-data="{ showSignupError: false }" x-init="@if($errors->any() && !$errors->has('login')) showSignupError = true @endif" x-show="showSignupError" x-cloak class="modal p-4" :class="{ 'modal-open': showSignupError }">
    <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-xl shadow-xl" @click.stop>
        <div class="p-8">
            <h2 class="text-lg font-bold text-red-700 mb-2">Please fix the following:</h2>
            <ul class="list-disc pl-5 text-sm text-red-700 space-y-1 mb-4">
                @foreach ($errors->all() as $error)
                    @if ($error !== $errors->first('login'))
                        <li>{{ $error }}</li>
                    @endif
                @endforeach
            </ul>
            <button @click="showSignupError = false" class="mt-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">Close</button>
        </div>
    </div>
    <button type="button" class="modal-backdrop fixed inset-0 backdrop-blur-sm" @click="showSignupError = false">close</button>
</div>
