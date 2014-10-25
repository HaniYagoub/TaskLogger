var tasks = [];
var logDate;

$(document).ready(function() {
    logDate = $('#log-date').html();
    initTasks();

    $('#start_task_button').on('click', function() {
        stopRunningTasks();
        createTask();
    });
    $('#tasks').on('click', '.stop_task_button', function() {
        stopRunningTasks();
    });
    $('#tasks').on('click', '.continue_task_button', function() {
        stopRunningTasks();
        startWork($(this).data('task'));
    });

    $('#tasks').on('focusout', '.description', function(){
        setDescription($(this).parents('li.task').attr('id'), $(this).html());
    });
});

function initTasks()
{
    $.ajax({
        url: Routing.generate('get_tasks',{
           date: logDate
        })
    }).done(function(data){
        data.forEach(function(task){
           tasks[task.id] = task;
        });
        renderTasks();
    });
}

function renderTasks()
{
    tasks.forEach(function(task){
        renderTask(task);
    });
}

function renderTask(task)
{
    var buttonContent = 'Continue Task';
    var buttonClass = 'btn btn-xs btn-primary continue_task_button';

    if (task.workLogs[task.workLogs.length - 1] === undefined || task.workLogs[task.workLogs.length - 1].duration === null) {
        buttonContent = 'Stop Task';
        buttonClass = 'btn btn-xs btn-danger stop_task_button';
    }

    var duration = 0;
    task.workLogs.forEach(function(workLog){
        if (workLog.duration !== null) {
            var d = new Date(workLog.duration.date);
            duration += d.getHours()*60*60+d.getMinutes()*60+d.getSeconds();
        }
    });
    var durationTime = new Date(duration*1000);
    durationTime = durationTime.getUTCHours() + 'h ' + durationTime.getMinutes() + 'm ' +  durationTime.getSeconds() + 's';

    if ($('.task#'+task.id).length > 0) {
        $('.task#'+task.id).remove();
    }

    $("#tasks").loadTemplate("/bundles/hanikitasklogger/templates/task.html", {
        id: task.id,
        startedAt: task.id + ' ' +new Date(task.createdAt.date).toLocaleTimeString(),
        updatedAt: new Date(task.updatedAt.date).toLocaleTimeString(),
        description: task.description,
        duration: durationTime,
        buttonContent: buttonContent,
        buttonClass: buttonClass
    }, {
        prepend: true
        //overwriteCache: true
    });
}

function setDescription(taskId, description)
{
    $.ajax({
        url: Routing.generate('update_task_description', {
            taskId: taskId,
            description: strip(description)
        }),
        method: 'post'
    }).done(function(description){
        tasks[taskId].description = description;
        tasks[taskId].updatedAt = new Date().toLocaleTimeString();
        $('.task#'+taskId).find('.description').html(description);
        $('.task#'+taskId).find('.updated-at').html(new Date().toLocaleTimeString());
    });
}

function stopRunningTasks()
{
    var running;
    tasks.forEach(function(task){
        running = false;
        task.workLogs.forEach(function(workLog){
            if (workLog.duration === null) {
                running = true;
            }
        });
        if (running) {
            stopTask(task.id);
        }
    });
}

function stopTask(taskId)
{
    $.ajax({
        url: Routing.generate('stop_task', {id: taskId}),
        async: false
    }).done(function(data){
        tasks[taskId] = data;
        renderTask(tasks[taskId]);
    }).fail(function(){
        return false;
    });

    return true;
}

function createTask()
{
    $.ajax({
        url: Routing.generate('create_task'),
        method: 'post'
    }).done(function(task){
        tasks[task.id] = task;
        startWork(task.id);
        $('li.task#'+task.id).find('div.description').focus();
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
        tasks[taskId].workLogs.push(data);
        renderTask(tasks[taskId]);
        return true;
    }).fail(function(){
        return false;
    });
}

function strip(html)
{
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}