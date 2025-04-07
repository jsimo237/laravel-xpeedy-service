<?php

namespace Kirago\Xpeedy;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {


    public function register(){
      //  $this->registerConsoleCommands();
    }

    public function boot(){

        $this->offerPublishing();

        $this->publishes(
            [
                __DIR__.'/../config/xpeedy-service.php' => config_path('xpeedy-service.php'),
            ],
            'xpeedy-service-config'
        ) ;
        // Charger les routes API
       // $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

       // $this->loadRoutesFrom(__DIR__ . '/routes/api.php'); // bas = src
    }

    protected function offerPublishing(): void{

        if (!$this->app->runningInConsole()) {
            return;
        }

        // function not available and 'publish' not relevant in Lumen
        if (! function_exists('config_path')) {
            return;
        }

        $this->loadMigrationsFrom([
            database_path('migrations'),
        ]);

    }


    protected function registerConsoleCommands(): void{
        if (!$this->app->runningInConsole()) {
            return;
        }

        if ($commands = config("business-core.console-commands") ?? []){
            $this->commands($commands);
        }
        $this->commands([

        ]);

    }


}
