<div id="toast" class="toast-stack" role="region" aria-label="Thông báo">
    @if(session('success'))
        <x-notification-card type="success" :messages="[session('success')]" />
    @endif
    @if(session('cart_notice'))
        <x-notification-card type="info" :messages="[session('cart_notice')]" />
    @endif
    @if($errors->any())
        <x-notification-card type="error" :messages="$errors->all()" />
    @endif
</div>
<template id="toast-template"><x-notification-card :shown="false" /></template>
