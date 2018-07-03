<?php

namespace main\app\ctrl;

use main\app\classes\AgileLogic;
use main\app\classes\UserLogic;
use main\app\classes\RewriteUrl;
use main\app\model\agile\SprintModel;
use main\app\model\agile\AgileBoardModel;
use main\app\model\agile\AgileBoardColumnModel;
use main\app\model\issue\IssueModel;
use main\app\model\project\ProjectModel;
use main\app\model\project\ProjectModuleModel;
use main\app\model\issue\IssuePriorityModel;
use main\app\model\issue\IssueTypeModel;
use main\app\model\issue\IssueStatusModel;
use main\app\model\issue\IssueResolveModel;

class Agile extends BaseUserCtrl
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * index
     */
    public function backlog()
    {
        $data = [];
        $data['title'] = 'Backlog';
        $data['page_type'] = 'backlog';
        $data['nav_links_active'] = 'backlog';
        $data['sub_nav_active'] = 'all';
        $data['query_str'] = http_build_query($_GET);
        $data = RewriteUrl::setProjectData($data);

        $data['sprint_id'] = '';

        $this->render('gitlab/agile/backlog.php', $data);
    }

    /**
     * index
     */
    public function sprint()
    {
        $data = [];
        $data['title'] = 'Backlog';
        $data['page_type'] = 'sprint';
        $data['nav_links_active'] = 'sprints';
        $data['sub_nav_active'] = 'all';
        $data['query_str'] = http_build_query($_GET);
        $data = RewriteUrl::setProjectData($data);

        $sprintId = '';
        if (isset($_GET['_target'][2])) {
            $sprintId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $sprintId = (int)$_GET['id'];
        }
        if (empty($sprintId)) {
            $model = new SprintModel();
            $activeSprint = $model->getActive($data['project_id']);
            if (isset($activeSprint['id'])) {
                $sprintId = $activeSprint['id'];
            } else {
                $sprints = $model->getItemsByProject($data['project_id']);
                if (isset($sprints[0]['id'])) {
                    $sprintId = $sprints[0]['id'];
                }
            }
        }

        $data['sprint_id'] = $sprintId;

        $this->render('gitlab/agile/backlog.php', $data);
    }


    public function board()
    {
        $data = [];
        $data['title'] = 'Backlog';
        $data['nav_links_active'] = 'kanban';
        $data['sub_nav_active'] = 'all';
        $data['query_str'] = http_build_query($_GET);
        $data = RewriteUrl::setProjectData($data);

        $agileLogic = new AgileLogic();
        $data['boards'] = $agileLogic->getBoardsByProject($data['project_id']);


        $data['active_sprint_id'] = '';
        $model = new SprintModel();
        $activeSprint = $model->getActive($data['project_id']);
        if (isset($activeSprint['id'])) {
            $data['active_sprint_id'] = $activeSprint['id'];
        } else {
            $sprints = $model->getItemsByProject($data['project_id']);
            if (isset($sprints[0]['id'])) {
                $data['active_sprint_id'] = $sprints[0]['id'];
            }
        }

        $this->render('gitlab/agile/board.php', $data);
    }

    /**
     *  fetch backlog
     */
    public function fetchBacklogIssues()
    {
        $projectId = null;
        if (isset($_GET['_target'][2])) {
            $projectId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $projectId = (int)$_GET['id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('failed,params_error');
        }
        $issueLogic = new AgileLogic();
        list($fetchRet, $issues) = $issueLogic->getBacklogIssues($projectId);
        if ($fetchRet) {
            $data['issues'] = $issues;
        } else {
            $this->ajaxFailed('server_error:' . $issues);
        }
        $data['sprints'] = $issueLogic->getSprints($projectId);

        $model = new IssuePriorityModel();
        $data['priority'] = $model->getAll();

        $issueTypeModel = new IssueTypeModel();
        $data['issue_types'] = $issueTypeModel->getAll();

        $model = new IssueStatusModel();
        $data['issue_status'] = $model->getAll();

        $model = new IssueResolveModel();
        $data['issue_resolve'] = $model->getAll();

        $userLogic = new UserLogic();
        $data['users'] = $userLogic->getAllNormalUser();
        unset($userLogic);


        $this->ajaxSuccess('success', $data);
    }

    public function fetchClosedIssuesByProject()
    {
        $projectId = null;
        if (isset($_GET['_target'][2])) {
            $projectId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $projectId = (int)$_GET['id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('failed,params_error');
        }
        $issueLogic = new AgileLogic();
        $data['issues'] = $issueLogic->getClosedIssues($projectId);

        $model = new IssuePriorityModel();
        $data['priority'] = $model->getAll();

        $issueTypeModel = new IssueTypeModel();
        $data['issue_types'] = $issueTypeModel->getAll();

        $model = new IssueStatusModel();
        $data['issue_status'] = $model->getAll();

        $userLogic = new UserLogic();
        $data['users'] = $userLogic->getAllNormalUser();
        unset($userLogic);


        $this->ajaxSuccess('success', $data);
    }


    /**
     *  fetch project's sprints
     */
    public function fetchSprints()
    {
        $projectId = null;
        $issueId = null;
        if (isset($_GET['issue_id'])) {
            $issueId = (int)$_GET['issue_id'];
        }
        if (isset($_GET['_target'][2])) {
            $projectId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['project_id'])) {
            $projectId = (int)$_GET['project_id'];
        }
        if ($issueId && !$projectId) {
            $issueModel = new IssueModel();
            $projectId = $issueModel->getById($issueId)['project_id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('param_error');
        }
        $sprintModel = new SprintModel();
        $data['sprints'] = $sprintModel->getItemsByProject($projectId);

        $this->ajaxSuccess('success', $data);
    }

    public function addSprint()
    {
        $projectId = null;
        if (isset($_GET['_target'][2])) {
            $projectId = (int)$_GET['_target'][2];
        }
        if (isset($_POST['project_id'])) {
            $projectId = (int)$_POST['project_id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('param_error');
        }
        $model = new SprintModel();
        $activeSprint = $model->getActive($projectId);

        $info = [];
        $info['project_id'] = $projectId;
        $info['name'] = $_POST['params']['name'];
        $info['active'] = '0';
        if (!isset($activeSprint['id'])) {
            $info['active'] = '1';
        }
        if (isset($_POST['params']['description'])) {
            $info['description'] = $_POST['params']['description'];
        }
        if (isset($_POST['params']['start_date'])) {
            $info['start_date'] = $_POST['params']['start_date'];
        }
        if (isset($_POST['params']['start_date'])) {
            $info['end_date'] = $_POST['params']['end_date'];
        }
        $sprintModel = new SprintModel();
        list($ret, $msg) = $sprintModel->insert($info);
        if ($ret) {
            $this->ajaxSuccess('ok');
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }

    public function joinSprint()
    {
        $sprintId = null;
        $issueId = null;
        if (isset($_POST['issue_id'])) {
            $issueId = (int)$_POST['issue_id'];
        }
        if (isset($_POST['sprint_id'])) {
            $sprintId = (int)$_POST['sprint_id'];
        }
        if (empty($sprintId) || empty($issueId)) {
            $this->ajaxFailed('param_error');
        }
        $issueModel = new IssueModel();
        $issue = $issueModel->getById($issueId);
        if (!isset($issue['id'])) {
            $this->ajaxFailed('param_error', 'Issue not exists');
        }

        $sprintModel = new SprintModel();
        $sprint = $sprintModel->getItemById($sprintId);
        if (!isset($sprint['id'])) {
            $this->ajaxFailed('param_error', 'Sprint not exists');
        }

        if ($issue['project_id'] != $sprint['project_id']) {
            $this->ajaxFailed('failed', 'No same project');
        }

        $model = new IssueModel();
        list($ret, $msg) = $model->updateById($issueId, ['sprint' => $sprintId]);
        if ($ret) {
            $this->ajaxSuccess('success');
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }

    /**
     * 设置 Sprint 为活动状态
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function setSprintActive()
    {
        $sprintId = null;
        if (isset($_POST['sprint_id'])) {
            $sprintId = (int)$_POST['sprint_id'];
        }
        if (empty($sprintId)) {
            $this->ajaxFailed('param_error');
        }
        $sprintModel = new SprintModel();
        $sprint = $sprintModel->getItemById($sprintId);
        if (!isset($sprint['id'])) {
            $this->ajaxFailed('param_error', 'Sprint not exists');
        }

        $sprintModel->update(['active' => '0'], ['project_id' => $sprint['project_id']]);
        list($ret, $msg) = $sprintModel->updateById($sprintId, ['active' => '1']);
        if ($ret) {
            $this->ajaxSuccess('success');
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }

    public function joinBacklog()
    {
        $issueId = null;
        if (isset($_POST['issue_id'])) {
            $issueId = (int)$_POST['issue_id'];
        }

        if (empty($issueId)) {
            $this->ajaxFailed('param_error');
        }
        $model = new IssueModel();
        list($ret, $msg) = $model->updateById($issueId, ['sprint' => AgileLogic::BACKLOG_VALUE]);
        if ($ret) {
            $this->ajaxSuccess('success');
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }

    /**
     *  fetch sprint's issues
     */
    public function fetchSprintIssues()
    {
        $sprintId = null;
        if (isset($_GET['_target'][2])) {
            $sprintId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $sprintId = (int)$_GET['id'];
        }
        if (empty($sprintId)) {
            $this->ajaxFailed('failed,params_error');
        }
        $sprintModel = new SprintModel();
        $sprint = $sprintModel->getItemById($sprintId);
        if (empty($sprint)) {
            $sprint = new \stdClass();
        }
        $data['sprint'] = $sprint;
        $issueLogic = new AgileLogic();
        $data['issues'] = $issueLogic->getSprintIssues($sprintId);

        $model = new IssuePriorityModel();
        $data['priority'] = $model->getAll();

        $issueTypeModel = new IssueTypeModel();
        $data['issue_types'] = $issueTypeModel->getAll();

        $model = new IssueStatusModel();
        $data['issue_status'] = $model->getAll();

        $userLogic = new UserLogic();
        $data['users'] = $userLogic->getAllNormalUser();
        unset($userLogic);

        $this->ajaxSuccess('success', $data);
    }

    /**
     * 获取活动的Sprint kanban信息
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function fetchBoardBySprint()
    {
        $projectId = null;
        $sprintId = null;
        if (isset($_GET['_target'][2])) {
            $sprintId = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $sprintId = (int)$_GET['id'];
        }
        if (isset($_GET['project_id'])) {
            $projectId = (int)$_GET['project_id'];
        }
        if (empty($sprintId)) {
            $this->ajaxFailed('failed,params_error');
        }
        $sprintModel = new SprintModel();
        $sprint = $sprintModel->getItemById($sprintId);
        if (empty($sprint)) {
            $this->ajaxFailed('failed,sprint_error');
        }
        if (empty($projectId) && !empty($sprint['project_id'])) {
            $projectId = $sprint['project_id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('failed,project_error');
        }

        $data['sprint'] = $sprint;
        $agileLogic = new AgileLogic();

        $boardId = AgileLogic::ACTIVE_SPRINT_BOARD_ID;
        $agileBoardModel = new AgileBoardModel();
        $board = $agileBoardModel->getById($boardId);
        if (empty($board)) {
            $this->ajaxFailed('board_no_found');
        }
        $data['board'] = $board;

        $agileBoardColumn = new AgileBoardColumnModel();
        $columns = $agileBoardColumn->getsByBoard($boardId);
        if (empty($columns)) {
            $this->ajaxFailed('board_no_column', []);
        }
        foreach ($columns as &$column) {
            $column['issues'] = [];
        }

        list($fetchRet, $msg) = $agileLogic->getBoardColumnBySprint($sprintId, $columns);
        if (!$fetchRet) {
            $this->ajaxFailed('server_error:' . $msg);
        }
        $closedColumn = $column;
        $closedColumn['name'] = 'Closed';
        $closedColumn['data'] = '';
        $closedColumn['issues'] = $agileLogic->getClosedIssues($projectId);
        $closedColumn['count'] = count($closedColumn['issues']);
        $columns[] = $closedColumn;

        $userLogic = new UserLogic();
        $data['users'] = $userLogic->getAllNormalUser();
        unset($userLogic);

        list($fetchRet, $issues) = $agileLogic->getBacklogIssues($projectId);
        if ($fetchRet) {
            $data['backlogs'] = $issues;
        } else {
            $this->ajaxFailed('server_error:' . $issues);
        }

        if ($fetchRet) {
            $data['columns'] = $columns;
            $this->ajaxSuccess('success', $data);
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }

    /**
     * 通过 board_id 获取 Kanban 信息
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function fetchBoardById()
    {
        $projectId = null;
        $id = null;
        if (isset($_GET['_target'][2])) {
            $id = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }
        if (isset($_GET['project_id'])) {
            $projectId = (int)$_GET['project_id'];
        }
        $model = new AgileBoardModel();
        $board = $model->getById($id);
        if (empty($board)) {
            $this->ajaxFailed('board_no_found');
        }
        $data['board'] = $board;
        if (empty($projectId) && !empty($board['project_id'])) {
            $projectId = (int)$board['project_id'];
        }
        if (empty($projectId)) {
            $this->ajaxFailed('params_error,project_error');
        }

        $model = new AgileBoardColumnModel();
        $columns = $model->getsByBoard($id);
        if (empty($columns)) {
            $this->ajaxFailed('board_no_column', []);
        }
        foreach ($columns as &$column) {
            $column['issues'] = [];
        }
        $agileLogic = new AgileLogic();
        $userLogic = new UserLogic();
        $data['users'] = $userLogic->getAllNormalUser();
        unset($userLogic);

        list($fetchRet, $issues) = $agileLogic->getBacklogIssues($projectId);
        if ($fetchRet) {
            $data['backlogs'] = $issues;
        } else {
            $this->ajaxFailed('server_error:' . $issues);
        }

        if ($board['type'] == 'label') {
            list($fetchRet, $msg) = $agileLogic->getBoardColumnByLabel($projectId, $columns);
        } else {
            list($fetchRet, $msg) = $agileLogic->getBoardColumnCommon($projectId, $columns, $board['type']);
        }
        $closedColumn = $column;
        $closedColumn['name'] = 'Closed';
        $closedColumn['data'] = '';
        $closedColumn['issues'] = $agileLogic->getClosedIssues($projectId);
        $closedColumn['count'] = count($closedColumn['issues']);
        $columns[] = $closedColumn;
        if ($fetchRet) {
            $data['columns'] = $columns;
            $this->ajaxSuccess('success', $columns);
        } else {
            $this->ajaxFailed('server_error:' . $msg);
        }
    }
}
