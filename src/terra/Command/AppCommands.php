<?php

namespace terra\Command;

class AppCommands extends Command {
  
  /**
   * Add an app to your system.
   *
   * @param string $name The system name of the app.
   * @param string $repository_url URL of the git repository.
   * @param string $description Description of the app (optional).
   */
  public function appAdd($name = NULL, $repository_url = NULL, $description = NULL) {
    
    $this->getAnswer($name, 'System name of the app: ');
    $this->getAnswer($repository_url, 'Git repository of the URL: ');
    $this->getAnswer($description, 'Description: ');
    
    $this->say("Adding $name with $repository_url and $description");
    
    $app = [
      'name' => $name,
      'description' => $description,
      'repo' => $repository_url,
    ];
    
    $this->config->add('apps', $name, $app);
    $this->config->save();
  }
  
  /**
   * Remove an app from the system.
   *
   * @param string $name The system name of the app.
   */
  public function appRemove($name = NULL, $opts = ['force' => false]) {
    $this->getApp($name);
//    $this->getAnswer($name, 'App to remove: ');
    
    $app = $this->config->get('apps', $name);
    print_r($app);
    if (!$app) {
      throw new \Exception("App $name not found!");
    }
    
    if ($opts['no-interaction'] || $this->confirm('Are you sure you wish to remove this app from your system?')) {
      
      $this->config->remove('apps', $name);
      $this->config->save();
      $this->say("App $name has been removed.");
    }
  }
}