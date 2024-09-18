<?php

namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'pool');

// Project repository
set('repository', 'ssh://git@muclgt01.group7.int:29418/PHP/pool.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', []);
set('shared_dirs', []);

// Writable dirs by web server
set('writable_dirs', []);
set('allow_anonymous_stats', false);

// Hosts

host('testlap01.group7.int')
    ->user('deployer')
    ->identityFile('~/.ssh/id_rsa_deployer')
    ->stage('staging')
    ->forwardAgent(true)
    ->set('deploy_path', '/virtualweb/apps/{{application}}');

host('g7lap15.group7.int')
    ->user('deployer')
    ->identityFile('~/.ssh/id_rsa_deployer')
    ->stage('production')
    ->forwardAgent(true)
    ->set('deploy_path', '/srv/www/apps/{{application}}');

// Tasks

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    //    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success',
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');