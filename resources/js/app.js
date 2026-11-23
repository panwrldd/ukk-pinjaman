import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],
        push(type, message, title = null) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type, message, title });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });
});

Alpine.start();
