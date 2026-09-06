@extends('layouts.storefront')

@section('seo')
    <title>Customer Address Book | The Botanical Haven</title>
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="space-y-8" x-data="{ showNewModal: false, editingAddress: null }">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :breadcrumbs="[
        ['name' => 'Account', 'url' => route('account.dashboard')],
        ['name' => 'Address Book', 'url' => '']
    ]" />

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-stone-200 pb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-stone-900 tracking-tight">Delivery Address Book</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">
                Manage your plant shipping destinations. Exactly one primary address is marked as default.
            </p>
        </div>

        <button type="button"
                @click="showNewModal = true; editingAddress = null;"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs shadow-md transition-colors self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add New Address</span>
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Addresses Grid --}}
    @if($addresses->isEmpty())
        <div class="p-12 text-center bg-white rounded-3xl border border-stone-200/80 shadow-sm space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 text-emerald-800 flex items-center justify-center">
                <svg class="w-8 h-8 stroke-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-stone-900">No Addresses Saved</h2>
            <p class="text-sm text-stone-500 max-w-md mx-auto">
                Add your home or garden delivery address for faster checkout when Phase 6 launches.
            </p>
            <div class="pt-2">
                <button type="button"
                        @click="showNewModal = true"
                        class="px-5 py-2.5 rounded-xl bg-emerald-800 text-white font-bold text-xs shadow-md">
                    Add Your First Address
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($addresses as $addr)
                <div class="relative bg-white rounded-3xl border {{ $addr->is_default ? 'border-emerald-500 shadow-md ring-1 ring-emerald-500/30' : 'border-stone-200/80 shadow-sm' }} p-6 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $addr->address_type->value === 'home' ? 'bg-emerald-50 text-emerald-800' : ($addr->address_type->value === 'work' ? 'bg-blue-50 text-blue-800' : 'bg-stone-100 text-stone-700') }}">
                                {{ $addr->address_type->value }}
                            </span>

                            @if($addr->is_default)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Default Delivery
                                </span>
                            @endif
                        </div>

                        <div>
                            <h3 class="font-bold text-stone-900 text-base">{{ $addr->recipient_name }}</h3>
                            <p class="text-xs text-stone-500 font-mono mt-0.5">{{ $addr->phone }}</p>
                        </div>

                        <div class="text-xs text-stone-600 space-y-0.5 leading-relaxed">
                            <p>{{ $addr->address_line_1 }}</p>
                            @if($addr->address_line_2)
                                <p>{{ $addr->address_line_2 }}</p>
                            @endif
                            <p class="font-medium text-stone-800">
                                {{ $addr->city }}, {{ $addr->state }} — {{ $addr->postal_code }}
                            </p>
                            <p class="text-[11px] text-stone-400">{{ $addr->country }}</p>
                        </div>
                    </div>

                    {{-- Actions Row --}}
                    <div class="pt-4 border-t border-stone-100 flex items-center justify-between text-xs font-semibold">
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click='editingAddress = @json($addr); showNewModal = true;'
                                    class="text-emerald-700 hover:text-emerald-900">
                                Edit
                            </button>

                            @if(!$addr->is_default)
                                <form method="POST" action="{{ route('account.addresses.set-default', $addr) }}">
                                    @csrf
                                    <button type="submit" class="text-stone-500 hover:text-stone-800">
                                        Set as Default
                                    </button>
                                </form>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('account.addresses.destroy', $addr) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Delete this address? If it was default, the oldest remaining address will become default.')"
                                    class="text-rose-600 hover:text-rose-800">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Address Modal (Create / Edit) --}}
    <div x-show="showNewModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/60 backdrop-blur-xs"
         role="dialog"
         aria-modal="true">
        <div @click.away="showNewModal = false"
             class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8 shadow-2xl border border-stone-200">
            <div class="flex items-center justify-between border-b border-stone-100 pb-4 mb-6">
                <h2 class="text-xl font-bold text-stone-900" x-text="editingAddress ? 'Edit Address' : 'Add New Address'"></h2>
                <button type="button" @click="showNewModal = false" class="text-stone-400 hover:text-stone-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST"
                  :action="editingAddress ? ('/account/addresses/' + editingAddress.id) : '{{ route('account.addresses.store') }}'"
                  class="space-y-4">
                @csrf
                <template x-if="editingAddress">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Type Selection --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1.5">Address Type</label>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-1.5 text-xs text-stone-700 cursor-pointer">
                            <input type="radio" name="address_type" value="home" :checked="!editingAddress || editingAddress.address_type === 'home'" class="text-emerald-700 focus:ring-emerald-500">
                            <span>Home</span>
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-stone-700 cursor-pointer">
                            <input type="radio" name="address_type" value="work" :checked="editingAddress && editingAddress.address_type === 'work'" class="text-emerald-700 focus:ring-emerald-500">
                            <span>Work</span>
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-stone-700 cursor-pointer">
                            <input type="radio" name="address_type" value="other" :checked="editingAddress && editingAddress.address_type === 'other'" class="text-emerald-700 focus:ring-emerald-500">
                            <span>Other</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="addr-name" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">Recipient Name *</label>
                        <input type="text" id="addr-name" name="recipient_name" :value="editingAddress ? editingAddress.recipient_name : ''" required class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label for="addr-phone" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">Contact Phone *</label>
                        <input type="text" id="addr-phone" name="phone" :value="editingAddress ? editingAddress.phone : ''" required class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                    </div>
                </div>

                <div>
                    <label for="addr-line1" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">Address Line 1 *</label>
                    <input type="text" id="addr-line1" name="address_line_1" :value="editingAddress ? editingAddress.address_line_1 : ''" required placeholder="House/Flat No., Apartment, Street" class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                </div>

                <div>
                    <label for="addr-line2" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">Address Line 2 (Optional)</label>
                    <input type="text" id="addr-line2" name="address_line_2" :value="editingAddress ? editingAddress.address_line_2 : ''" placeholder="Area, Sector, Colony" class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="addr-city" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">City *</label>
                        <input type="text" id="addr-city" name="city" :value="editingAddress ? editingAddress.city : ''" required class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label for="addr-state" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">State *</label>
                        <input type="text" id="addr-state" name="state" :value="editingAddress ? editingAddress.state : ''" required class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label for="addr-postal" class="block text-xs font-bold uppercase tracking-wider text-stone-700 mb-1">PIN Code *</label>
                        <input type="text" id="addr-postal" name="postal_code" :value="editingAddress ? editingAddress.postal_code : ''" required class="w-full px-3 py-2 rounded-xl border border-stone-300 text-xs focus:ring-1 focus:ring-emerald-600">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_default" value="1" :checked="editingAddress ? editingAddress.is_default : false" class="rounded border-stone-300 text-emerald-700 focus:ring-emerald-500">
                        <span class="text-xs font-medium text-stone-700">Make this my primary / default delivery address</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-stone-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showNewModal = false" class="px-4 py-2 rounded-xl bg-stone-100 text-stone-700 font-semibold text-xs hover:bg-stone-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-800 text-white font-bold text-xs hover:bg-emerald-900 shadow-sm">
                        Save Address
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
