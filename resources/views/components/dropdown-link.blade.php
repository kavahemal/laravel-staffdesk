@props(['as' => 'a'])

<{{ $as }} {{ $attributes->merge(['class' => 'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden [&_svg]:size-5 [&_svg]:text-gray-500']) }}>{{ $slot }}</{{ $as }}>
