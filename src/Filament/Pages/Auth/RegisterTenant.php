<?php

namespace Lyre\Filament\Pages\Auth;

use Lyre\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Lyre\Billing\Models\Subscription;
use Lyre\Billing\Models\SubscriptionPlan;

class RegisterTenant extends \Filament\Pages\Tenancy\RegisterTenant
{
    protected $redirect = '/';

    public static function getLabel(): string
    {
        return 'Create Organization';
    }

    public function form(Form $form): Form
    {
        $host = app_url_host();

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Organization Name')
                    ->required()
                    ->maxLength(255)
                    ->debounce(500)
                    ->unique(Tenant::class, 'name', ignorable: fn(?Tenant $record) => $record)
                // ->afterStateUpdated(
                //     fn(callable $set, $state) =>
                //     $set('domain', Str::slug($state))
                // ),
                // TextInput::make('domain')
                //     ->label('Domain Name')
                //     ->prefix('')
                //     ->suffix(".{$host}")
                //     ->required()
                //     ->rules(['alpha_dash', 'min:3'])
                //     ->placeholder('e.g. mysite')
                // ->unique(Domain::class, 'domain', ignorable: fn(?Tenant $record) => $record?->primaryDomain)
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        // $host = app_url_host();
        // $fqdn = "{$data['domain']}.{$host}";

        $tenant = Tenant::create([
            'name' => $data['name'],
            'user_id' => auth()->id(),
            // 'fqdn' => $fqdn,
        ]);

        $subscriptionPlan = SubscriptionPlan::where('name', 'Developer')->first();

        if ($subscriptionPlan) {
            $endDate = $subscriptionPlan->trial_days > 0 ? now()->addDays($subscriptionPlan->trial_days) : now()->{$subscriptionPlan->billing_cycle == 'annually' ? 'addYear' : 'addMonth'}();

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'subscription_plan_id' => $subscriptionPlan->id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => $endDate,
                'auto_renew' => true,
            ]);

            $subscription->associateWithTenant($tenant);
        }

        $subscriptionPlan->associateWithTenant($tenant);
        auth()->user()->associateWithTenant($tenant);

        // $domain = $tenant->createDomain([
        //     'domain' => $fqdn,
        // ]);

        // $tenant->sync(
        //     \App\Models\User::find(auth()->id()),
        //     $tenant,
        //     $domain
        // );
        // $tenant->sync(
        //     $tenant,
        //     $domain
        // );
        // $tenant->sync(
        //     $domain
        // );

        // $token = Crypt::encrypt([
        //     'auth_id' => auth()->id(),
        //     'tenant_id' => $tenant->id,
        //     'domain' => $fqdn,
        // ]);

        // TODO: Kigathi - Should create the tenant's subdomain in the actual server configuration

        // $this->setRedirectUrl("{$scheme}://$fqdn/auto-tenant/{$token}");

        return $tenant;
    }

    public static function canView(): bool
    {
        // try {
        //     return authorize('create', Filament::getTenantModel())->allowed();
        // } catch (AuthorizationException $exception) {
        //     return $exception->toResponse()->allowed();
        // }
        return true;
    }

    protected function setRedirectUrl($redirectUrl): string
    {
        $this->redirect = $redirectUrl;
        return $redirectUrl;
    }

    // protected function getRedirectUrl(): ?string
    // {
    //     return $this->redirect ?? Filament::getUrl($this->tenant);
    // }
}
