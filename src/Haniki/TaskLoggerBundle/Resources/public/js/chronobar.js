var workLogs = [];

var startTime = 9 * 60 * 60; //Début à 9h
var endTime = 21 * 60 * 60; //Fin à 21h

function initChronobar()
{
    displayChronobarEvents();

    $('#chronobar').on('mouseover', '.progress-bar', function() {
        $('.progress-bar[data-task="'+$(this).data('task')+'"]').addClass('highlight');
        $('.task#'+$(this).data('task')).addClass('highlight');
    });
    $('#chronobar').on('mouseleave', '.progress-bar', function() {
        $('.progress-bar[data-task="'+$(this).data('task')+'"]').removeClass('highlight');
        $('.task#'+$(this).data('task')).removeClass('highlight');
    });
    $('#tasks').on('mouseover', '.task', function() {
        $('.progress-bar[data-task="'+$(this).attr('id')+'"]').addClass('highlight');
        $(this).addClass('highlight');
    });
    $('#tasks').on('mouseleave', '.task', function() {
        $('.progress-bar[data-task="'+$(this).attr('id')+'"]').removeClass('highlight');
        $(this).removeClass('highlight');
    });
}

function displayChronobarEvents()
{
    $('#chronobar').html('');
    tasks.forEach(function(task){
        if (task.workLogs !== undefined) {
            task.workLogs.forEach(function(workLog) {
                displayChronobarEvent(workLog);
            });
        }
    });
}

function displayChronobarEvent(workLog)
{
    var duration = getWorkLogDuration(workLog);
    var widthPercent = duration > 0 ? duration / (endTime - startTime) * 100 : 0;
    var progressBar = $('#chronobar .progress-bar[data-worklog="'+workLog.id+'"]');

    if (progressBar.length > 0) {
        progressBar.css('width', widthPercent+'%');
    } else {
        var start = dateToSeconds(new Date(workLog.startedAt.date));
        var offset = start > 0 ? (start - startTime) / (endTime - startTime) * 100 : 0;
        var progressBarClass = ($('#chronobar .progress-bar').length % 2 == 0) ? 'progress-bar-info' : 'progress-bar-success';

        $('#chronobar').append('<div data-task="'+workLog.taskId+'" data-worklog="'+workLog.id+'" class="progress-bar '+progressBarClass+'" style="width: ' + widthPercent + '%; left: ' + offset + '%"></div>');
    }
}

function refreshProgressBar(task)
{
    if (task.workLogs !== undefined && task.workLogs[task.workLogs.length - 1] !== undefined) {
        displayChronobarEvent(task.workLogs[task.workLogs.length - 1]);
    }
}

