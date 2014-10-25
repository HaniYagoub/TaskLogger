$(document).ready(function() {
    $('#start_task_button').on('click', function() {
        stopCurrentTask();
        createTask();
    });
    $('#stop_task_button').on('click', function() {
        stopCurrentTask();
    });
    $('.continue_task_button').on('click', function() {
        if(startWork($(this).data('task'))) {
            $(this).remove();
        }
    });
});

function stopCurrentTask()
{
    if($('#stop_task_button').size() > 0) {
        var taskId = $('#stop_task_button').data('task');
        $.ajax({
            url: Routing.generate('stop_task', {id: taskId})
        }).done(function(data){
            console.log(data);
            $('#stop_task_button').hide();
            //$('#stop_task_button').parent().append(data);
            //$('#stop_task_button').remove();
        }).fail(function(){
            return false;
        });
    }

    return true;
}

function createTask()
{
    $.ajax({
        url: Routing.generate('create_task'),
        method: 'post'
    }).done(function(task){
        console.log(task);
        renderTask(task);
        startWork(task.id);
    }).fail(function(){
        return false;
    });
}

function startWork(taskId)
{
    $.ajax({
        url: Routing.generate('start_work', {taskId: taskId}),
        method: 'post'
    }).done(function(data){
        console.log(data);
        return true;
    }).fail(function(){
        return false;
    });
}

function renderTask(task)
{
    $("#tasks").loadTemplate("bundles/hanikitasklogger/templates/task.html",
    {
        id: task.id,
        updatedAt: task.updatedAt,
        description: task.description
    },
    {
        prepend: true
    });
}