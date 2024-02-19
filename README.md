# Overview

## Technologies

This project uses Vue 3 (https://vuejs.org/guide/introduction.html) on the front-end and Laravel 10 (https://laravel.com/docs/10.x) on the back-end.  The two technologies interact via REST API calls.  Users are authenticated via a Bearer JWT.

Node v16.15.1 and Vite v4 (https://vitejs.dev/guide/) is used for development and asset bundling/transpiling.

The MDBootstrap framework (https://mdbootstrap.com/docs/vue/layout/grid/) is used for styling, layout, and UI components.

## Initial Installation Steps

Obtain a sample `.env` file from Ryan.  Modify the `APP_URL`, `APP_VITE_HOST`, `DB_HOST`, and other settings as appropriate for your environment.

`composer install`

Node can be installed natively or via NVM (https://github.com/nvm-sh/nvm)
- `nvm install 16.15.1`
- `nvm use 16.15.1`

`npm install`

`npm run prod`

`php artisan storage:link`

`php artisan migrate`

`php artisan db:seed Initialize`

Obtain the latest DB dumps of these tables from Ryan and import into your MySQL database:
- `dialer_access_areas`
- `dialer_access_roles`
- `dialer_access_roles_areas`
- `dialer_access_roles_notifications`
- `dialer_notification_types`

`mkdir storage/app/chunks storage/app/uploads storage/app/exports`

Set appropriate permissions on the storage directory so that Laravel can write to it.  This will depend on your local setup of which user or group Nginx or Apache runs under.
`sudo chgrp -R webdev storage && sudo chmod -R g+rwX storage`

In order to have queued jobs run automatically, install and configure Supervisor (https://laravel.com/docs/10.x/queues#supervisor-configuration).  Alternatively, jobs can be run manually with `php artisan queue:work --stop-when-empty`.  Or, you can have jobs run immediately without a queue by setting `QUEUE_CONNECTION=sync` in `.env`.  If you run jobs automatically in the background, remember that you will need to run `php artisan queue:restart` after making **any** code changes in order for the job processor to pickup the latest changes.

You will need to add a user account in order to interact with the UI.  Run `php artisan admin:adduser 'Your Name' 'your@email.com' 'your_password'`. 

## Access Areas

The project uses a very granular system of access controls so that different permissions can be given to different users based on access role.   A user has one Access Role.  An Access Role can have one or more Access Areas.  An Access Area can be assigned to one or more Access Roles.

Each screen or feature in the system is considered an "Access Area".  When a new screen or feature is set up, it must first be added via Admin > Access > Access Roles in the GUI with a unique slug.  This new entry must be communicated to other developers on the project so we all have the latest information.

The new Access Area must be added to the appropriate Access Roles via Admin > Access > Access Roles.  If you're already logged in, log out and back in for the change to take effect on your account.

The new slug that is added will be used as a text string in the project, either in Vue as `authStore.hasAccessToArea('SLUG')` or in Laravel as `$request->user()->hasAccessToArea("SLUG")`.

## Notification Types

Similar to Access Areas, each outbound email notification in the system is considered a "Notification Type". 

A user has one Access Role.  An Access Role can have one or more Notification Types.  A Notification Type can be assigned to one or more Access Roles.

When a new email notification is set up, it must first be added via Admin > Access > Notification Types in the GUI with a unique slug.  This new entry must be communicated to other developers on the project so we all have the latest information.

The new Notification Type must be added to the appropriate Access Roles via Admin > Access > Access Roles.

The new slug that is added will be used as a text string in the project in Laravel as `DialerNotificationType::getEmailsForNotificationType('SLUG', true)`.

## Best Practices

- Create separate branches, based off `master`, for each new task.  Never create a new branch from a different task's branch.
- Never merge to `master`.  Create a PR for Ryan to review.  He'll merge if it's ready to be deployed.
- Use Laravel DB migrations (https://laravel.com/docs/10.x/migrations) for any new DB tables or changes to existing DB tables.
- When adding a new DB model, add an observer entry to `EventServiceProvider.php` so CRUD operations will be logged in the `audit_logs` table.  Most models can use the `GenericObserver` class.
- Use Laravel Queues (https://laravel.com/docs/10.x/queues) for any background processing or email sending.
- Whenever possible, use MDBootstrap components (https://mdbootstrap.com/docs/vue/components/accordion/), layouts (https://mdbootstrap.com/docs/vue/layout/grid/), and styling (https://mdbootstrap.com/docs/vue/content-styles/colors/) rather than native HTML inputs or custom CSS.  The project also has some of its own components, like `SmartSelectAjax.vue`, which extend MDBootstrap's functionality.
- Validate all user input.  See example uses of `ApiJsonValidator::validate()` in existing Controllers.
- Use `ErrorResponse::json()` in Controllers to return errors to Vue.
- Use `DataTableFields::displayOrExport()` in combination with `CustomDatatable.vue` or `CustomDatatableAjax.vue` for data tables. 

# Deploying the Project

### Production deployment

`git pull && composer install --optimize-autoloader --no-dev && php artisan config:cache && php artisan event:cache && php artisan route:cache && php artisan view:cache && php artisan queue:restart && sudo systemctl reload php-fpm && nvm use 16.15.1 && npm i && npm run prod`

### Development Vite server

`nvm use 16.15.1`

`npm run dev`
