<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InstallThumbsCommand extends Command
{
    protected $signature = 'voyager:install-thumbs';
    protected $description = 'Install the Voyager Thumbs module';

    public function handle()
    {
        $this->info('Voyager Thumbs module installing...');
        
        // Создаем таблицу thumbs
        if (Schema::hasTable('thumbs')) {
            $this->info('Thumbs table already exists');
            if (!Schema::hasColumn('thumbs', 'blur')){
                Schema::table('thumbs', function($table) {
                    $table->integer('blur')->nullable()->default(0);
                });
                $this->info('Thumbs table updated');
            }
            if (!Schema::hasColumn('thumbs', 'canvas_color')){
                Schema::table('thumbs', function($table) {
                    $table->string('canvas_color')->nullable();
                });
                $this->info('Canvas color column added');
            }
        } else {
            Schema::create('thumbs', function (Blueprint $table) {
                $table->id();
                $table->string('mark')->unique();
                $table->integer('width')->nullable();
                $table->integer('height')->nullable();
                $table->boolean('cover')->nullable();
                $table->boolean('fix_canvas')->nullable();
                $table->boolean('upsize')->nullable();
                $table->integer('quality')->nullable()->default(90);
                $table->integer('blur')->nullable()->default(0);
                $table->string('canvas_color')->nullable();
                $table->timestamps();
            });

            $this->info('Thumbs table created');
        }

        // Добавляем data_type для thumbs
        if (Schema::hasTable('data_types')) {
            if(!DB::table('data_types')->where('name', 'thumbs')->first()){
                DB::table('data_types')->insert([
                    'name' => 'thumbs',
                    'slug' => 'thumbs',
                    'display_name_singular' => 'Thumb',
                    'display_name_plural' => 'Thumbs',
                    'model_name' => 'TCG\\Voyager\\Models\\Thumb',
                    'generate_permissions' => 1,
                    'server_side' => 0,
                    'details' => '{"order_column":null,"order_display_column":null,"order_direction":"asc","default_search_key":null,"scope":null}',
                ]);
                $this->info('Data type created');
            }
            
            $data_type = DB::table('data_types')->where('name', 'thumbs')->first();
            if ($data_type && Schema::hasTable('data_rows')) {
                if(!DB::table('data_rows')->where('data_type_id', $data_type->id)->first()){
                    $data = [
                        ['data_type_id' => $data_type->id, 'field' => 'id', 'type' => 'text', 'display_name' => 'ID', 'required' => 1, 'browse' => 0, 'read' => 0, 'edit' => 0, 'add' => 0, 'delete' => 0, 'details' => '{}', 'order' => 1],
                        ['data_type_id' => $data_type->id, 'field' => 'mark', 'type' => 'text', 'display_name' => 'Mark', 'required' => 1, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 2],
                        ['data_type_id' => $data_type->id, 'field' => 'width', 'type' => 'number', 'display_name' => 'Width', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 3],
                        ['data_type_id' => $data_type->id, 'field' => 'height', 'type' => 'number', 'display_name' => 'Height', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 4],
                        ['data_type_id' => $data_type->id, 'field' => 'cover', 'type' => 'checkbox', 'display_name' => 'Cover', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 5],
                        ['data_type_id' => $data_type->id, 'field' => 'fix_canvas', 'type' => 'checkbox', 'display_name' => 'Fix Canvas', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 6],
                        ['data_type_id' => $data_type->id, 'field' => 'upsize', 'type' => 'checkbox', 'display_name' => 'Upsize', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 7],
                        ['data_type_id' => $data_type->id, 'field' => 'quality', 'type' => 'number', 'display_name' => 'Quality', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 8],
                        ['data_type_id' => $data_type->id, 'field' => 'blur', 'type' => 'number', 'display_name' => 'Blur', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 9],
                        ['data_type_id' => $data_type->id, 'field' => 'canvas_color', 'type' => 'color', 'display_name' => 'Canvas Color', 'required' => 0, 'browse' => 1, 'read' => 1, 'edit' => 1, 'add' => 1, 'delete' => 1, 'details' => '{}', 'order' => 10],
                        ['data_type_id' => $data_type->id, 'field' => 'created_at', 'type' => 'timestamp', 'display_name' => 'Created At', 'required' => 0, 'browse' => 0, 'read' => 0, 'edit' => 0, 'add' => 0, 'delete' => 0, 'details' => '{}', 'order' => 11],
                        ['data_type_id' => $data_type->id, 'field' => 'updated_at', 'type' => 'timestamp', 'display_name' => 'Updated At', 'required' => 0, 'browse' => 0, 'read' => 0, 'edit' => 0, 'add' => 0, 'delete' => 0, 'details' => '{}', 'order' => 12],
                    ];

                    DB::table('data_rows')->insert($data);
                    $this->info('Data rows created');
                }
            }
        }

        // Добавляем пункт меню
        if (Schema::hasTable('menu_items')) {
            if(!DB::table('menu_items')->where([['menu_id', 1], ['parent_id', 5],['title', 'Thumbs']])->first()){
                DB::table('menu_items')->insert([
                    'menu_id' => 1,
                    'title' => 'Thumbs',
                    'url' => '',
                    'target' => '_self',
                    'icon_class' => 'voyager-resize-full',
                    'color' => '#000000',
                    'parent_id' => '5',
                    'order' => '50',
                    'route' => 'voyager.thumbs.index',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $this->info('Menu item created');
            }
        }

        // Создаем права доступа
        if (Schema::hasTable('permissions')) {
            $permissions = ['browse_thumbs','read_thumbs','edit_thumbs','add_thumbs','delete_thumbs'];
            if(!DB::table('permissions')->where('table_name', 'thumbs')->whereIn('key', $permissions)->count()){
                $data = [];
                foreach ($permissions as $key => $permission) {
                    $data[] = [
                        'key' => $permission,
                        'table_name' => 'thumbs',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                }

                DB::table('permissions')->insert($data);
            }

            if (Schema::hasTable('permission_role')) {
                $permission_ids = DB::table('permissions')->where('table_name', 'thumbs')->whereIn('key', $permissions)->pluck('id')->toArray();
                if(!DB::table('permission_role')->whereIn('permission_id', $permission_ids)->count()) {
                    if ($permission_ids) {
                        $data = [];
                        foreach ($permission_ids as $key => $id) {
                            $data[] = ['permission_id' => $id, 'role_id' => 1];
                        }

                        DB::table('permission_role')->insert($data);
                        $this->info('Permissions created');
                    }
                }
            }
        }

        // Создаем директории для хранения thumbnails
        $thumbsPath = storage_path('app/public/_thumbs');
        if (!file_exists($thumbsPath)) {
            mkdir($thumbsPath, 0755, true);
            $this->info('Thumbs directory created');
        }

        $placeholdersPath = storage_path('app/public/_thumbs/placeholders');
        if (!file_exists($placeholdersPath)) {
            mkdir($placeholdersPath, 0755, true);
            $this->info('Placeholders directory created');
        }

        $this->info('Voyager Thumbs module installed successfully!');
        $this->info('Run: php artisan cache:clear');
    }
}
