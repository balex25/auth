<?php

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['view-auth-setup']);
name('auth.setup.preview.passwordless');

?>

<x-auth::layouts.app title="{{ __('auth.passwordless.page_title') }}">
    <x-auth::elements.passwordless-login :auto-submit="false" form-action="#" />
</x-auth::layouts.app>
