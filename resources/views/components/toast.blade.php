<div
    x-data="{
        notifications: [],
        add(type, message) {
            const id = Date.now() + Math.random();
            this.notifications.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 5500);
        },
        dismiss(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }"
    x-init="
        @if(session('success'))
            add('success', @js(session('success')));
        @endif
        @if(session('error'))
            add('error', @js(session('error')));
        @endif
        @if(session('info'))
            add('info', @js(session('info')));
        @endif
        @if($errors->any())
            add('error', @js($errors->first()));
        @endif
    "
    class="no-print pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2.5"
>
    <template x-for="note in notifications" :key="note.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-white p-3.5 shadow-lg"
            :class="{
                'border-emerald-200': note.type === 'success',
                'border-rose-200': note.type === 'error',
                'border-blue-200': note.type === 'info'
            }"
        >
            <div class="mt-0.5 shrink-0">
                <template x-if="note.type === 'success'">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <x-icon name="check" class="h-3.5 w-3.5" />
                    </span>
                </template>
                <template x-if="note.type === 'error'">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                        <x-icon name="alert-triangle" class="h-3.5 w-3.5" />
                    </span>
                </template>
                <template x-if="note.type === 'info'">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        <x-icon name="info" class="h-3.5 w-3.5" />
                    </span>
                </template>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-900" x-text="note.type === 'success' ? 'Berhasil' : (note.type === 'error' ? 'Perhatian' : 'Informasi')"></p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-600" x-text="note.message"></p>
            </div>
            <button type="button" @click="dismiss(note.id)" class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                <x-icon name="x" class="h-3.5 w-3.5" />
            </button>
        </div>
    </template>
</div>
