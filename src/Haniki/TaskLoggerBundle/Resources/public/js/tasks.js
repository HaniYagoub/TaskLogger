var tasks = [];
var logDate,
    interval;

$(document).ready(function() {
    logDate = $('#log-date').html();
    interval = setInterval(refreshTaskDuration, 1000);

    initTasks();

    $('#log-date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayBtn: true,
        todayHighlight: true
    }).on('changeDate', function(e){
        $('#log-date').html(e.format());
        window.location.assign(Routing.generate('show_tasks', {date: e.format()}));
    });
    $('#start_task_button').on('click', function() {
        stopRunningTasks();
        createTask();
    });
    $('#tasks').on('click', '.stop_task_button', function() {
        stopRunningTasks();
    });
    $('#tasks').on('click', '.continue_task_button', function() {
        stopRunningTasks();
        startWork($(this).data('task'), false);
        if (new Date(logDate).toDateString() !== new Date().toDateString()) {
            window.location.assign(Routing.generate('show_tasks'));
        }
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

function initDescription(taskId, showDescriptionForm)
{
    var $elem = $('.task#'+taskId).find('.description');
    $elem.editable({
        type: 'text',
        pk: taskId,
        url: Routing.generate('update_task_description'),
        emptytext: 'Empty',
        mode: 'inline',
        success: function(description){
            setDescription(taskId, description);
        }
    });
    if (showDescriptionForm) {
        $elem.editable('show');
    }
}

function renderTasks()
{
    tasks.forEach(function(task){
        renderTask(task);
    });
}

function renderTask(task, showDescriptionForm)
{
    showDescriptionForm = (showDescriptionForm !== undefined) && showDescriptionForm;

    var buttonContent = 'Continue Task';
    var buttonClass = 'btn btn-xs btn-primary continue_task_button';

    if (task.workLogs[task.workLogs.length - 1] === undefined || task.workLogs[task.workLogs.length - 1].duration === null) {
        buttonContent = 'Stop Task';
        buttonClass = 'btn btn-xs btn-danger stop_task_button';
    }

    var durationTime = secondsToTime(getTaskDuration(task));

    removeTask(task.id);

    $("#tasks").loadTemplate("/bundles/hanikitasklogger/templates/task.html", {
        id: task.id,
        createdAt: new Date(task.createdAt.date).toLocaleTimeString(),
        updatedAt: new Date(task.updatedAt.date).toLocaleTimeString(),
        description: task.description,
        duration: durationTime,
        buttonContent: buttonContent,
        buttonClass: buttonClass
    }, {
        prepend: true,
        success: function() {
            initDescription(task.id, showDescriptionForm);
            sortTasks();
        }
        //overwriteCache: true
    });
}

function removeTask(taskId)
{
    if ($('.task#'+taskId).length > 0) {
        $('.task#'+taskId).remove();
    }
}

function setDescription(taskId, description)
{
    tasks[taskId].description = description;
    tasks[taskId].updatedAt = new Date().toLocaleTimeString();
    $('.task#'+taskId).find('.updated-at').html(new Date().toLocaleTimeString());
    sortTasks();
}

function stopRunningTasks()
{
    tasks.forEach(function(task){
        if (isTaskRunning(task)) {
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
        startWork(task.id, true);
    }).fail(function(){
        return false;
    });
}

function startWork(taskId, showDescriptionForm)
{
    $.ajax({
        url: Routing.generate('start_work', {taskId: taskId}),
        method: 'post'
    }).done(function(data){
        tasks[taskId].workLogs.push(data);
        renderTask(tasks[taskId], showDescriptionForm);
        return true;
    }).fail(function(){
        return false;
    });
}

function isTaskRunning(task)
{
    var length = task.workLogs.length;
    return task.workLogs[length - 1] === undefined || task.workLogs[length - 1].duration === null;
}

function getRunningTask()
{
    var result = false;
    tasks.forEach(function(task){
        if (isTaskRunning(task)) {
            result = task;
        }
    });
    return result;
}

function refreshTaskDuration()
{
    var task = getRunningTask();
    if (task !== false) {
        $('.task#'+task.id).find('.duration').html(secondsToTime(getTaskDuration(task)));
    }
}

function getTaskDuration(task)
{
    var duration = 0;
    task.workLogs.forEach(function(workLog){
        if (workLog.duration !== null) {
            var d = new Date(workLog.duration.date);
            duration += d.getHours()*60*60+d.getMinutes()*60+d.getSeconds();
        } else {
            var startedAt = new Date(workLog.startedAt.date);
            var d = new Date();
            duration += parseInt((d - startedAt)/1000);
        }
    });

    return duration;
}

function secondsToTime(seconds)
{
    var time = new Date(seconds*1000);
    return time.getUTCHours() + 'h ' + time.getMinutes() + 'm ' +  time.getSeconds() + 's';
}

function strip(html)
{
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}

function sortTasks()
{
    var runningTask = getRunningTask();
    $('.task').sortElements(function(a, b){
        if (runningTask.id == $(a).attr('id')) {
            return -1;
        }
        var d1 = new Date('1970-01-01T' + $(a).find('.updated-at').html()).toTimeString();
        var d2 = new Date('1970-01-01T' + $(b).find('.updated-at').html()).toTimeString();
        return  d1 < d2 ? 1 : -1;
    });
}