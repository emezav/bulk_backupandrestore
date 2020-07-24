define(['jquery', 'core/config'], function($, config) {

  var session = '';
  var session_key = '';
  var outdir = '';
  var total_courses = 0;
  var current = 0;
  var record = 0;

  var backup_users = 0;
  var backup_blocks = 0;

  var backup_category = function() {

    var course_table = $('#course-table');

    session = course_table.data('session');
    session_key = course_table.data('key');
    outdir = course_table.data('outdir');
    backup_users = course_table.data('backupusers');
    backup_blocks = course_table.data('backupblocks');

    var course_rows = $('.course-row');

    total_courses = course_rows.length;

    $('.progress-bar').attr('aria-valuenow', 0);
    $('.progress-bar').attr('style', 'width: 0');
    $('.progress-bar').html('0%');

    $('.progress').removeClass('d-none');

    if (total_courses > 0) {
      current = 1;
      backup_course();
    }
  };

  var backup_course = function() {

    record = current;

    var courseId = $('.course-row-' + record).data('course');

    if (record > 0 && record <= total_courses) {

      var $url = config.wwwroot + '/admin/tool/bulk_backupandrestore/backup_course.php';
      $('#course-' + courseId).addClass('text-primary');
      $('#course-' + courseId).addClass('border-primary');


      $('#course-' + courseId + ' > td.status').html('');

      $.ajax({
        type: "POST",
        url:$url,
        dataType: 'json',
        data: {
          'id': courseId,
          'sesskey': session,
          'key': session_key,
          'outdir' : outdir,
          'backupusers' : backup_users,
          'backupblocks' : backup_blocks,
          'last' : (record == total_courses)?true:false
        },
        success: function(data) {
          $('#course-' + courseId).removeClass('border-primary');
          $('#course-' + courseId).removeClass('text-primary');

          var currentProgress = Math.floor((current / total_courses) * 100);
          $('.progress-bar').attr('aria-valuenow', currentProgress);
          $('.progress-bar').attr('style', 'width: ' + currentProgress + '%');
          $('.progress-bar').html(currentProgress + '%');
          $('#course-' + courseId + ' > td.status').html(data.message);

          if (data.status == true) {
            $('#course-' + courseId).addClass('text-success');
          }else {
            $('#course-' + courseId).addClass('text-danger');
          }
           
          if (record < total_courses) {
            current++;
            backup_course();
          }else {
            $('.progress').hide();
            $('.result').html(data.result);
          }
        },
        error: function() {
          $('#course-' + courseId).removeClass('border-primary');
          $('#course-' + courseId).removeClass('text-primary');
          $('#course-' + courseId).addClass('text-danger');

          var currentProgress = Math.floor((current / total_courses) * 100);
          $('.progress-bar').attr('aria-valuenow', currentProgress);
          $('.progress-bar').attr('style', 'width: ' + currentProgress + '%');
          $('.progress-bar').html(currentProgress + '%');

          if (record <= total_courses) {
            current++;
            backup_course();
          }else {
            $('.progress').hide();
          }
        }
      });
    }
  };


  return  {
    init: function() {
      $('#backup_category').on('click', function() {
        backup_category();
      });
    },
  };
});
