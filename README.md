# Fuse - The Safety Switch for Livewire

Fuse is a static analysis checker for your [Laravel Livewire](https://livewire.laravel.com/) apps.  
Think of it like an electrician testing your code’s wiring before you go live. ⚡

---

## 🚀 What is Fuse?

Livewire makes it simple to build dynamic interfaces with Laravel, but it’s also easy to introduce subtle errors:
- A `wire:model` bound to a property that doesn’t exist.
- Dispatching events with typos.
- Mismatched listeners and parameters.
- Alpine `$wire` calls to non-existent methods.

**Fuse catches these problems before they ever hit production.**

## Checks Performed by Fuse

Fuse runs a static analysis over your Livewire app to validate that everything is wired up correctly. Currently there are 8 checks performed by Fuse (feel free to submit a PR to add more):

### 1. $wire Binding Validation

Validates that every $wire reference — whether in Blade (wire:model, wire:click, etc.) or AlpineJS ($wire.foo, $wire.method()) — maps to a real public property or method on your Livewire component. Prevents broken bindings before they hit production.

### 2. Prop & Binding Validation
- Ensures wire:model="foo" corresponds to a real public property $foo.
- Validates nested bindings (wire:model="user.name") map to actual nested properties, catching silent binding bugs early.

### 3. Event & Listener Contract Checks
- Confirms that dispatch('event', params) has a matching listener method that accepts the correct number and type of params.
- Detects typos and mismatches in event names (dispatch('userRegistred') vs listen('userRegistered')).

### 4. Alpine Event Validation

Validates Alpine event usage, ensuring $dispatch and x-on events align with Livewire listeners. Keeps your frontend interactions connected and prevents silent event mismatches.

### 5. Method Visibility & Signature Validation
- Blocks invalid calls to private/protected methods via $wire.
- Ensures methods bound to wire:click or wire:change don’t require missing or mismatched parameters.

### 6. Dead Wire Detection
- Detects Livewire props/methods that are never referenced in Blade templates (unused/dead code).
- Detects Blade bindings (e.g. wire:model="bar") that don’t map to an existing $bar property.

### 7. Computed & Magic Property Checks

Verifies that computed properties (getFooProperty) are referenced correctly as $wire.foo, not mis-typed as $wire.fooProperty.

### 8. Lifecycle Hook Validation
- Confirms lifecycle hooks like mount, hydrate, or updatingFoo exist and have correct signatures.
- Warns on misspellings (e.g. updatedFoo vs updatingFoo) before they cause subtle bugs.

## Installation

```bash
composer require devdojo/fuse --dev
```

## Usage

Run Fuse against your project:

```bash
php artisan fuse:check
```

This will scan your Livewire components and Blade templates, reporting any wiring issues it finds.

# Why Fuse?

- Catch bugs early – before they sneak into production.
- Improve confidence – every $wire, dispatch, and listener is validated.
- Save debugging time – no more “why isn’t this event firing?” moments.

## Contributing

Pull requests are welcome! If you’d like to add new checks or improve existing ones, please open an issue or submit a PR.

## License

MIT © DevDojo