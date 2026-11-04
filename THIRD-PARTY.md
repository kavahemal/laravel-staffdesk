# Third-party code

This project includes code from the following open-source projects. Everything else in the repository is covered by the project's own [LICENSE](LICENSE).

## SweetAlert2

- Source: https://sweetalert2.github.io – MIT, © Tristan Edwards & Limon Monte
- Installed through npm (not copied into this repository); used for confirm dialogs and toasts in `resources/js/app.js`.

## flatpickr

- Source: https://flatpickr.js.org – MIT, © Gregory Petrosyan
- Installed through npm (not copied into this repository); date picker set up in `resources/js/app.js`, used via `<x-date-input>`.

## Heroicons

- Icons: https://heroicons.com – MIT, © Tailwind Labs
- Blade components: [blade-ui-kit/blade-heroicons](https://github.com/blade-ui-kit/blade-heroicons) – MIT, © Dries Vints
- Installed through Composer (not copied into this repository); used as `<x-heroicon-o-… />`.

## TailAdmin – Free Tailwind CSS Admin Dashboard (Laravel version)

- Source: https://github.com/TailAdmin/tailadmin-laravel
- Licence: MIT
- Used in this project (adapted, light mode only):
  - Theme tokens – colours, font sizes, shadows, sidebar menu utilities and date-picker styles in `resources/css/app.css`
  - Layout, sidebar and header markup in `resources/views/layouts/`
  - Sidebar state store in `resources/js/app.js`
  - Sign-in page design in `resources/views/layouts/guest.blade.php` and `resources/views/auth/`
  - `public/images/grid-01.svg`

```
MIT License

Copyright (c) 2025 TailAdmin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
