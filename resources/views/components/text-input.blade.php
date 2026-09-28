@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#1f4b3f] focus:ring-[#1f4b3f] rounded-lg shadow-sm']) }}>