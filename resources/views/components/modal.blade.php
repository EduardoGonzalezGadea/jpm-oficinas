@props(['id', 'title', 'size' => '', 'centered' => false])

<div wire:ignore.self class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog {{ $size }} {{ $centered ? 'modal-dialog-centered' : '' }}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            @if(isset($footer))
            <div class="modal-footer">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>