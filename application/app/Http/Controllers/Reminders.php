<?php

/** --------------------------------------------------------------------------------
 * This controller manages all the business logic for goals
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Reminders\CreateResponse;
use App\Http\Responses\Reminders\DestroyResponse;
use App\Http\Responses\Reminders\EditResponse;
use App\Http\Responses\Reminders\IndexResponse;
use App\Http\Responses\Reminders\ShowResponse;
use App\Http\Responses\Reminders\StoreResponse;
use App\Http\Responses\Reminders\UpdateResponse;
use App\Permissions\ReminderPermissions;
use App\Repositories\CategoryRepository;
use App\Repositories\ReminderRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserRepository;
use App\Rules\NoTags;
use Illuminate\Http\Request;
use Validator;

class Reminders extends Controller {

    /**
     * The reminder repository instance.
     */
    protected $reminderrepo;

    /**
     * The tags repository instance.
     */
    protected $tagrepo;

    /**
     * The user repository instance.
     */
    protected $userrepo;

    /**
     * The reminder permission instance.
     */
    protected $reminderpermissions;

    public function __construct(
        ReminderRepository $reminderrepo,
        TagRepository $tagrepo,
        UserRepository $userrepo,
        ReminderPermissions $reminderpermissions) {

        //parent
        parent::__construct();

        //authenticated
        $this->middleware('auth');

        $this->middleware('remindersMiddlewareIndex')->only([
            'index',
            'store',
        ]);

        $this->middleware('remindersMiddlewareCreate')->only([
            'create',
            'store',
        ]);

        $this->middleware('remindersMiddlewareShow')->only([
            'show',
        ]);

        $this->middleware('remindersMiddlewareEdit')->only([
            'edit',
            'update',
        ]);

        $this->middleware('remindersMiddlewareDestroy')->only([
            'destroy',
        ]);

        $this->reminderrepo = $reminderrepo;

        $this->tagrepo = $tagrepo;

        $this->userrepo = $userrepo;

        $this->reminderpermissions = $reminderpermissions;

    }

    /**
     * Display a listing of reminders
     * @param object CategoryRepository instance of the repository
     * @return blade view | ajax view
     */
    public function index(CategoryRepository $categoryrepo) {
       
        //default to user reminders if type is not set
        if (request('reminderresource_type')) 
        {
            // $page = $this->pageSettings('myreminders');
            $page = $this->pageSettings('myreminders');
        } else {
            $page = $this->pageSettings('reminders');
        }

        //get reminders
        $reminders = $this->reminderrepo->search();

        //apply some permissions
        if ($reminders) {
            foreach ($reminders as $reminder) {
                $this->applyPermissions($reminder);
            }
        }

        //reponse payload
        $payload = [
            'page' => $page,
            'reminders' => $reminders,
        ];

        //show the view
        return new IndexResponse($payload);
    }

    /**
     * Show the form for creating a new  goal
     * @param object CategoryRepository instance of the repository
     * @return \Illuminate\Http\Response
     */
    public function create(CategoryRepository $categoryrepo) {

        //get tags
        $tags = $this->tagrepo->getByType('reminders');

        //reponse payload
        $payload = [
            'page' => $this->pageSettings('create'),
            'tags' => $tags,
        ];

        //show the form
        return new CreateResponse($payload);
    }

    /**
     * Store a newly created goal in storage.
     * @return \Illuminate\Http\Response
     */
    public function store() {

        $messages = [];

        //validate
        $validator = Validator::make(request()->all(), [
            'reminder_title' => [
                'required',
                new NoTags,
            ],
            'reminder_description' => 'required',
            'reminder_date' => 'required',
            'tags' => [
                'bail',
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $key => $data) {
                        if (hasHTML($data)) {
                            return $fail(__('lang.tags_no_html'));
                        }
                    }
                },
            ],
        ], $messages);

        //errors
        if ($validator->fails()) {
            $errors = $validator->errors();
            $messages = '';
            foreach ($errors->all() as $message) {
                $messages .= "<li>$message</li>";
            }

            abort(409, $messages);
        }

        //create the reminder
        if (!$reminder_id = $this->reminderrepo->create()) {
            abort(409);
        }

        //add tags
        $this->tagrepo->add('reminder', $reminder_id);

        //get the reminder object (friendly for rendering in blade template)
        $reminders = $this->reminderrepo->search($reminder_id);

        //permissions
        $this->applyPermissions($reminders->first());

        //counting rows
        $rows = $this->reminderrepo->search();
        $count = $rows->total();

        //reponse payload
        $payload = [
            'reminders' => $reminders,
            'count' => $count,
        ];

        //process reponse
        return new StoreResponse($payload);

    }

    /**
     * display a reminder via ajax modal
     * @param int $id reminder id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {

        //get the reminder
        $reminder = $this->reminderrepo->search($id);

        //reminder not found
        if (!$reminder = $reminder->first()) {
            abort(409, __('lang.reminder_not_found'));
        }

        //reponse payload
        $payload = [
            'reminder' => $reminder,
        ];

        //process reponse
        return new ShowResponse($payload);
    }

    /**
     * Show the form for editing the specified  reminder
     * @param int $id reminder id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {

        //get the reminder
        $reminder = $this->reminderrepo->search($id);

        //get tags
        $tags = $this->tagrepo->getByResource('reminder', $id);

        //reminder not found
        if (!$reminder = $reminder->first()) {
            abort(409, __('lang.reminder_not_found'));
        }

        //reponse payload
        $payload = [
            'page' => $this->pageSettings('edit'),
            'reminder' => $reminder,
            'tags' => $tags,
        ];

        //response
        return new EditResponse($payload);
    }

    /**
     * Update the specified reminder in storage.
     * @param int $id reminder id
     * @return \Illuminate\Http\Response
     */
    public function update($id) {

        //custom error messages
        $messages = [];

        //validate
        $validator = Validator::make(request()->all(), [
            'reminder_title' => [
                'required',
                new NoTags,
            ],
            'reminder_description' => 'required',
            'reminder_date' => 'required',
            'tags' => [
                'bail',
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $key => $data) {
                        if (hasHTML($data)) {
                            return $fail(__('lang.tags_no_html'));
                        }
                    }
                },
            ],
        ], $messages);

        //errors
        if ($validator->fails()) {
            $errors = $validator->errors();
            $messages = '';
            foreach ($errors->all() as $message) {
                $messages .= "<li>$message</li>";
            }

            abort(409, $messages);
        }

        //update
        if (!$this->reminderrepo->update($id)) {
            abort(409);
        }

        //delete & update tags
        $this->tagrepo->delete('reminder', $id);
        $this->tagrepo->add('reminder', $id);

        //get reminder
        $reminders = $this->reminderrepo->search($id);

        $this->applyPermissions($reminders->first());

        //reponse payload
        $payload = [
            'reminders' => $reminders,
        ];

        //generate a response
        return new UpdateResponse($payload);
    }

    /**
     * Remove the specified goal from storage.
     * @param int $id goal id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {

        $reminder = \App\Models\Reminder::Where('reminder_id', $id)->first();

        //remove the item
        $reminder->delete();

        //reponse payload
        $payload = [
            'reminder_id' => $id,
        ];

        //generate a response
        return new DestroyResponse($payload);
    }

    /**
     * pass the file through the ProjectPermissions class and apply user permissions.
     * @param object reminder instance of the reminder model object
     * @return object
     */
    private function applyPermissions($reminder = '') {

        //sanity - make sure this is a valid file object
        if ($reminder instanceof \App\Models\Reminder) {
            //delete permissions
            $reminder->permission_edit_delete_reminder = $this->reminderpermissions->check('edit-delete', $reminder);
        }
    }
    /**
     * basic page setting for this section of the app
     * @param string $section page section (optional)
     * @param array $data any other data (optional)
     * @return array
     */
    private function pageSettings($section = '', $data = []) {

        //common settings
        $page = [
            'crumbs' => [
                __('lang.reminders'),
            ],
            'crumbs_special_class' => 'list-pages-crumbs',
            'page' => 'reminders',
            'no_results_message' => __('lang.no_results_found'),
            'mainmenu_reminders' => 'active',
            'sidepanel_id' => 'sidepanel-filter-reminders',
            'dynamic_search_url' => url('reminders/search?action=search&reminderresource_id=' . request('reminderresource_id') . '&reminderresource_type=' . request('reminderresource_type')),
            'add_button_classes' => 'add-edit-reminder-button',
            'load_more_button_route' => 'reminders',
            'source' => 'list',
        ];

        //default modal settings (modify for sepecif sections)
        $page += [
            'add_modal_title' => __('lang.add_reminder'),
            'add_modal_create_url' => url('reminders/create?reminderresource_id=' . request('reminderresource_id') . '&reminderresource_type=' . request('reminderresource_type')),
            'add_modal_action_url' => url('reminders?reminderresource_id=' . request('reminderresource_id') . '&reminderresource_type=' . request('reminderresource_type')),
            'add_modal_action_ajax_class' => '',
            'add_modal_action_ajax_loading_target' => 'commonModalBody',
            'add_modal_action_method' => 'POST',
        ];

        //reminders list page
        if ($section == 'reminders') {
            $page += [
                'meta_title' => __('lang.reminders'),
                'heading' => __('lang.reminders-'),
            ];
            if (request('source') == 'ext') {
                $page += [
                    'list_page_actions_size' => 'col-lg-12',
                ];
            }
            return $page;
        }

        //reminders list my goals
        if ($section == 'myreminders') {
            $page += [
                'meta_title' => __('lang.ny_reminders'),
                'heading' => __('lang.ny_reminders'),
            ];
            if (request('source') == 'ext') {
                $page += [
                    'list_page_actions_size' => 'col-lg-12',
                ];
            }
            return $page;
        }

        //reminder page
        if ($section == 'reminder') {
            //adjust
            $page['page'] = 'reminder';
            //add
            $page += [
                'crumbs' => [
                    __('lang.reminders'),
                ],
                'meta_title' => __('lang.reminders'),
                'reminder_id' => request()->segment(2),
                'section' => 'overview',
            ];
            //ajax loading and tabs
            $page += $this->setActiveTab(request()->segment(3));
            return $page;
        }

        //create new resource
        if ($section == 'create') {
            $page += [
                'section' => 'create',
            ];
            return $page;
        }

        //edit new resource
        if ($section == 'edit') {
            $page += [
                'section' => 'edit',
            ];
            return $page;
        }

        //return
        return $page;
    }

}