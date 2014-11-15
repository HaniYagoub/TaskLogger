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
            if (undefined !== data.error || undefined !== data.warning) {
                console.log(data);
            } else {
                issues[taskId] = data;
                displayJiraModal(data, taskId);
            }
        }).fail(function(data){
            console.log(data);
            resetButtons();
        });
    }
}

function displayJiraModal(data, taskId)
{
    var comment = tasks[taskId].description;
    $('#jira-modal .modal-title').html(data.key + ' : ' + data.fields.summary);
    $('#jira-modal .modal-body .modal-description').html(data.fields.description);
    $('#jira-modal .modal-body .modal-worklog-duration').html(secondsToTime(getTaskDuration(tasks[taskId])));
    $('#jira-modal .modal-body .modal-worklog-comment').val(comment.replace(/#[A-Za-z]+\-[0-9]+ */g, ''));
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
        url: Routing.generate('log_work_jira'),
        data: {
            taskId: taskId,
            comment: $('#jira-worklog-comment').val()
        },
        method: 'post'
    }).done(function(data){
        if (undefined !== data.error) {
            console.log(data);
        } else {
            console.log(data);
            $('#jira-modal').modal("hide");
        }
    }).fail(function(data){
        console.log(data);
    }).always(function(){
        $('#log-jira-work-button').button('reset');
    });
}