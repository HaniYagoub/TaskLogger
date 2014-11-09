var tasks = [];
var logDate,
    interval;

$(document).ready(function() {
    logDate = $('#log-date').html();
    interval = setInterval(refreshCounters, 1000);

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
    $('#tasks').on('click', '.task', function() {
        $(this).toggleClass('selected');
        if ($('.task.selected').length >= 2) {
            $('#merge-task-button').removeClass('hidden');
        } else {
            $('#merge-task-button').addClass('hidden');
        }
    });
    $('#tasks').on('click', '.task button', function(e) {
        e.stopPropagation();
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
    $('#merge-task-button').on('click', function() {
        mergeTasks();
    });
    $('#main').on('click', '.btn-loading', function(){
        $(this).button('loading');
    });

    initJiraActions();
});

function initTasks()
{
    $.ajax({
        url: Routing.generate('get_tasks',{
           date: logDate
        })
    }).done(function(data){
        data.forEach(function(task){
            if (task.workLogs !== undefined) {
                task.workLogs.forEach(function(workLog) {
                    workLog.taskId = task.id;
                });
            }
            tasks[task.id] = task;
        });
        renderTasks();
        refreshTotalTime();
        initChronobar();
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
        if (task !== undefined && task.id !== undefined) {
            renderTask(task);
        }
    });
}

function renderTask(task, showDescriptionForm)
{
    showDescriptionForm = (showDescriptionForm !== undefined) && showDescriptionForm;

    var buttonContent = 'Continue Task';
    var buttonClass = 'btn btn-sm btn-info btn-loading continue_task_button';

    if (task.workLogs !== undefined) {
        if (task.workLogs[task.workLogs.length - 1] === undefined || task.workLogs[task.workLogs.length - 1].duration === null) {
            buttonContent = 'Stop Task';
            buttonClass = 'btn btn-sm btn-danger btn-loading stop_task_button';
        }
    }

    var durationTime = secondsToTime(getTaskDuration(task));

    removeTask(task.id);
    if (task.createdAt !== undefined && task.updatedAt !== undefined) {
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
        data.taskId = taskId;
        tasks[taskId].workLogs.push(data);
        renderTask(tasks[taskId], showDescriptionForm);
        return true;
    }).fail(function(){
        return false;
    }).always(function(){
        $('#start_task_button').button('reset');
    });
}

function isTaskRunning(task)
{
    if (task.workLogs === undefined) {
        return false;
    }
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

function mergeTasks()
{
    var tasksIds = [];
    $('#tasks .task.selected').each(function() {
        tasksIds.push($(this).attr('id'));
    });

    if (tasksIds.length > 0) {
        $.ajax({
            url: Routing.generate('merge_tasks'),
            data: {tasksIds: tasksIds},
            method: 'post'
        }).done(function(task){
            tasksIds.forEach(function(taskId) {
                tasks.splice(taskId, 1);
                removeTask(taskId);
            });
            if (task.workLogs !== undefined) {
                task.workLogs.forEach(function(workLog){
                    workLog.taskId = task.id;
                });
            }
            tasks[task.id] = task;
            renderTask(tasks[task.id]);
            displayChronobarEvents();
            $('#merge-task-button').addClass('hidden');
        }).fail(function(data){
            console.log(data);
            return false;
        }).always(function(){
            $('#merge-task-button').button('reset');
        });
    } else {
        $('#merge-task-button').addClass('hidden');
        resetButtons();
    }
}

function refreshCounters()
{
    var task = getRunningTask();
    if (task !== false) {
        refreshTaskDuration(task);
        refreshTotalTime();
        refreshProgressBar(task);
    }
}

function refreshTaskDuration(task)
{
    $('.task#'+task.id).find('.duration').html(secondsToTime(getTaskDuration(task)));
}

function refreshTotalTime()
{
    var totalTime = 0;
    tasks.forEach(function(task){
        totalTime += getTaskDuration(task);
    });
    $('#total-worked-time').html(secondsToTime(totalTime));
}

function getTaskDuration(task)
{
    var duration = 0;
    if (task.workLogs !== undefined) {
        task.workLogs.forEach(function(workLog){
            duration += getWorkLogDuration(workLog);
        });
    }

    return duration;
}

function getWorkLogDuration(workLog)
{
    if (workLog.duration !== null && workLog.duration !== undefined) {
        var d = new Date(workLog.duration.date);
        return dateToSeconds(d);
    } else {
        var startedAt = new Date(workLog.startedAt.date);
        var d = new Date();
        return parseInt((d - startedAt)/1000);
    }
}

function dateToSeconds(date)
{
    return date.getHours()*60*60+date.getMinutes()*60+date.getSeconds();
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

function resetButtons()
{
    $('.btn-loading').removeClass('disabled');
    $('.btn-loading').removeAttr('disabled');
    $('.btn-loading').button('reset');
}