@props(['llms', 'DC', 'result'])
@if (session('llms'))
    @if (
        $DC == null ||
            array_diff(explode(',', trim($DC->first()->identifier, '{}')), $result->pluck('model_id')->toArray()) == []
    )
        <div class="flex flex-1 flex-col border border-black dark:border-white border-1 rounded-lg">
            <div class="flex px-2 scrollbar scrollbar-3 overflow-x-auto py-3 border-b border-black dark:border-white">
                @foreach ($llms as $llm)
                    <div
                        class="mx-1 flex-shrink-0 h-5 w-5 rounded-full border border-gray-400 dark:border-gray-900 bg-black flex items-center justify-center overflow-hidden">
                        <div class="h-full w-full"><img data-tooltip-target="llm_{{ $llm->id }}_chat"
                                data-tooltip-placement="top" class="h-full w-full"
                                src="{{ strpos($llm->image, 'data:image/png;base64') === 0 ? $llm->image : asset(Storage::url($llm->image)) }}">
                        </div>
                        <div id="llm_{{ $llm->id }}" role="tooltip"
                            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-600">
                            {{ $llm->name }}
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="overflow-y-auto scrollbar">
                @if (request()->user()->hasPerm('Room_update_new_chat'))
                    <div class="m-2 border border-green-400 border-1 rounded-lg overflow-hidden">
                        <form method="post" action="{{ route('room.new') }}">
                            <div class="flex items-end justify-end">
                                @csrf
                                @foreach ($llms as $llm)
                                    <input name="llm[]" value="{{ $llm->id }}" style="display:none;">
                                @endforeach
                                <button type='submit'
                                    class="flex menu-btn flex text-green-400 w-full h-12 overflow-y-auto scrollbar dark:hover:bg-gray-700 hover:bg-gray-200 {{ session('llms') ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300">
                                    <p
                                        class="flex-1 flex items-center my-auto justify-center text-center leading-none self-baseline">
                                        {{ __('New Chat') }}</p>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
                @if ($DC)
                    @foreach ($DC as $dc)
                        <div
                            class="m-2 overflow-hidden border border-black dark:border-white border-1 rounded-lg flex dark:hover:bg-gray-700 hover:bg-gray-200">
                            <a class="menu-btn text-gray-700 dark:text-white w-full flex justify-center items-center overflow-hidden {{ request()->route('room_id') == $dc->id ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300"
                                href="{{ route('room.chat', $dc->id) }}">
                                <p
                                    class="px-4 m-auto text-center leading-none truncate-text overflow-ellipsis overflow-hidden max-h-4">
                                    {{ $dc->name }}</p>
                            </a>
                            <nav x-data="{ open: false }"
                                class="{{ request()->route('room_id') == $dc->id ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300">
                                <x-dropdown align="top" width="48" data-dropdown-placement="right"
                                    fix="true">
                                    <x-slot name="trigger">
                                        <button class="p-3 text-white hover:text-gray-300"><svg width="24"
                                                height="24" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg" class="icon-md">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M3 12C3 10.8954 3.89543 10 5 10C6.10457 10 7 10.8954 7 12C7 13.1046 6.10457 14 5 14C3.89543 14 3 13.1046 3 12ZM10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12ZM17 12C17 10.8954 17.8954 10 19 10C20.1046 10 21 10.8954 21 12C21 13.1046 20.1046 14 19 14C17.8954 14 17 13.1046 17 12Z"
                                                    fill="currentColor"></path>
                                            </svg></button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link class="!text-green-500 hover:!text-green-600"
                                            href="{{ route('room.share', $dc->id) }}" target="_blank">
                                            {{ __('Share link') }}
                                        </x-dropdown-link>
                                        @if (request()->user()->hasPerm('Room_delete_chatroom'))
                                            <x-dropdown-link href="#" class="!text-red-500 hover:!text-red-600"
                                                onclick="event.preventDefault();$('#deleteChat input[name=id]').val({{$dc->id}});$('#deleteChat h3 span:eq(1)').text('{{$dc->name }}');" data-modal-target="delete_chat_modal"
                                                data-modal-toggle="delete_chat_modal">
                                                {{ __('Delete') }}
                                            </x-dropdown-link>
                                        @endif
                                    </x-slot>
                                </x-dropdown>
                            </nav>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

    @endif
@else
    @if (array_diff(explode(',', trim($DC->first()->identifier, '{}')), $result->pluck('model_id')->toArray()) == [])
        <div class="flex flex-1 flex-col border border-black dark:border-white border-1 rounded-lg">
            <div class="flex px-2 scrollbar scrollbar-3 overflow-x-auto py-3 border-b border-black dark:border-white">
                @foreach (App\Models\Chats::join('llms', 'llms.id', '=', 'llm_id')->where('user_id', Auth::user()->id)->where('roomID', $DC->first()->id)->orderby('llm_id')->get() as $chat)
                    <div
                        class="mx-1 flex-shrink-0 h-5 w-5 rounded-full border border-gray-400 dark:border-gray-900 bg-black flex items-center justify-center overflow-hidden">
                        <div class="h-full w-full"><img data-tooltip-target="llm_{{ $chat->llm_id }}_chat"
                                data-tooltip-placement="top" class="h-full w-full"
                                src="{{ strpos($chat->image, 'data:image/png;base64') === 0 ? $chat->image : asset(Storage::url($chat->image)) }}">
                        </div>
                        <div id="llm_{{ $chat->llm_id }}" role="tooltip"
                            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-600">
                            {{ $chat->name }}
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="overflow-y-auto scrollbar">
                @if (request()->user()->hasPerm('Room_update_new_chat'))
                    <div class="m-2 border border-green-400 border-1 rounded-lg overflow-hidden">
                        <form method="post" action="{{ route('room.new') }}">
                            <div class="flex items-end justify-end">
                                @csrf
                                @foreach ($llms as $llm)
                                    <input name="llm[]" value="{{ $llm->id }}" style="display:none;">
                                @endforeach
                                <button type='submit'
                                    class="flex menu-btn flex text-green-400 w-full h-12 overflow-y-auto scrollbar dark:hover:bg-gray-700 hover:bg-gray-200 {{ session('llms') ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300">
                                    <p
                                        class="flex-1 flex items-center my-auto justify-center text-center leading-none self-baseline">
                                        {{ __('Create Room') }}</p>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
                @foreach ($DC as $dc)
                    <div
                        class="m-2 overflow-hidden border border-black dark:border-white border-1 rounded-lg flex dark:hover:bg-gray-700 hover:bg-gray-200">
                        <a class="menu-btn text-gray-700 dark:text-white w-full flex justify-center items-center overflow-hidden {{ request()->route('room_id') == $dc->id ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300"
                            href="{{ route('room.chat', $dc->id) }}">
                            <p
                                class="px-4 m-auto text-center leading-none truncate-text overflow-ellipsis overflow-hidden max-h-4">
                                {{ $dc->name }}</p>
                        </a>
                        <nav x-data="{ open: false }"
                            class="{{ request()->route('room_id') == $dc->id ? 'bg-gray-200 dark:bg-gray-700' : '' }} transition duration-300">
                            <x-dropdown align="top" width="48" data-dropdown-placement="right" fix="true">
                                <x-slot name="trigger">
                                    <button class="p-3 text-white hover:text-gray-300"><svg width="24"
                                            height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" class="icon-md">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M3 12C3 10.8954 3.89543 10 5 10C6.10457 10 7 10.8954 7 12C7 13.1046 6.10457 14 5 14C3.89543 14 3 13.1046 3 12ZM10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12ZM17 12C17 10.8954 17.8954 10 19 10C20.1046 10 21 10.8954 21 12C21 13.1046 20.1046 14 19 14C17.8954 14 17 13.1046 17 12Z"
                                                fill="currentColor"></path>
                                        </svg></button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link class="!text-green-500 hover:!text-green-600"
                                        href="{{ route('room.share', $dc->id) }}" target="_blank">
                                        {{ __('Share link') }}
                                    </x-dropdown-link>
                                    @if (request()->user()->hasPerm('Room_delete_chatroom'))
                                        <x-dropdown-link href="#" class="!text-red-500 hover:!text-red-600"
                                            onclick="event.preventDefault();$('#deleteChat input[name=id]').val({{$dc->id}});$('#deleteChat h3 span:eq(1)').text('{{$dc->name }}');" data-modal-target="delete_chat_modal"
                                            data-modal-toggle="delete_chat_modal">
                                            {{ __('Delete') }}
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </nav>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif
