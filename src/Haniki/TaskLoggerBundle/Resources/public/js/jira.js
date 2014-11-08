var issues = [];

function initJiraActions()
{
    $('#tasks').on('click', '.jira_issue_button', function(){
        getJiraIssue($(this).data('task'));
    });
    $('#jira-modal').on('shown.bs.modal', function () {
        resetButtons();
    });
}

function getJiraIssue(taskId)
{
    if (issues[taskId] !== undefined) {
        displayJiraModal(issues[taskId], taskId);
    } else {
        $.ajax({
            url: Routing.generate('get_jira_issue', {taskId: taskId}),
            method: 'post'
        }).done(function(data){
            issues[taskId] = data;
            displayJiraModal(data, taskId);
        }).fail(function(data){
            $('#jira-modal .modal-title').html('Error');
            $('#jira-modal .modal-body .modal-description').html(data.responseJSON.error);
        });
    }
}

function displayJiraModal(data, taskId)
{
    $('#jira-modal .modal-title').html(data.key + ' : ' + data.fields.summary);
    $('#jira-modal .modal-body .modal-description').html(data.fields.description);
    $('#jira-modal .modal-body .modal-worklogs').html('Duration : ' + secondsToTime(getTaskDuration(tasks[taskId])) + '<br />Description : '+tasks[taskId].description);
    $('#jira-modal #log-jira-work-button').attr('data-task', taskId);
    $('#jira-modal #refresh-jira-issue').attr('data-task', taskId);
    $('#jira-modal').modal();
    $('#log-jira-work-button').one('click', function(){
        logWorkJira(taskId);
    });
}

function logWorkJira(taskId)
{
    stopTask(taskId);
    $.ajax({
        url: Routing.generate('log_work_jira', {taskId: taskId}),
        method: 'post'
    }).done(function(data){
        console.log(data);
        return true;
    }).fail(function(data){
        console.log(data);
        return true;
    }).always(function(){
        $('#log-jira-work-button').button('reset');
    });
}