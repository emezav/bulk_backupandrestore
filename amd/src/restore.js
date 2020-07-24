define(['jquery', 'core/config'], function($, config) {

  var session = '';
  var session_key = '';
  var total_records = 0;
  var current = 0;
  var record = 0;

  var restore_users = 0;
  var restore_blocks = 0;

  var restore_courses = function() {

    var course_table = $('#restore-table');

    session = course_table.data('session');
    session_key = course_table.data('key');

    var record_rows = $('.record-row');

    total_records = record_rows.length;

    $('.progress-bar').attr('aria-valuenow', 0);
    $('.progress-bar').attr('style', 'width: 0');
    $('.progress-bar').html('0%');

    $('.progress').removeClass('d-none');

    if (total_records > 0) {
      current = 1;
      restore_course();
    }
  };

  var restore_course = function() {

    record = current;

    var record_row = $('#record-' + record);

    var categoryId = record_row.data('category');
    var folder = record_row.data('folder');
    var filename = record_row.data('filename');
    var name = record_row.data('name');
    var shortname = record_row.data('shortname');
    var idnumber = record_row.data('idnumber');
    var restore_users = record_row.data('users');
    var restore_blocks = record_row.data('blocks');

    if (record > 0 && record <= total_records) {

      var $url = config.wwwroot + '/admin/tool/bulk_backupandrestore/restore_course.php';
      record_row.addClass('text-primary');
      record_row.addClass('border-primary');

      $('#record-' + record + ' > td.status').html('');

      $.ajax({
        type: "POST",
        url:$url,
        dataType: 'json',
        data: {
          'sesskey': session,
          'key': session_key,
          'category': categoryId,
          'folder': folder,
          'filename': filename,
          'name': name,
          'shortname': shortname,
          'idnumber': idnumber,
          'restoreusers': restore_users,
          'restoreblocks': restore_blocks,
          'last': (record == total_records)?true:false
        },
        success: function(data) {
          record_row.removeClass('border-primary');
          record_row.removeClass('text-danger');
          record_row.removeClass('text-primary');

          var currentProgress = Math.floor((current / total_records) * 100);
          $('.progress-bar').attr('aria-valuenow', currentProgress);
          $('.progress-bar').attr('style', 'width: ' + currentProgress + '%');
          $('.progress-bar').html(currentProgress + '%');
          $('#record-' + record + ' > td.status').html(data.message);

          if (data.status == true) {
            record_row.addClass('text-success');
          }else {
            record_row.addClass('text-danger');
          }
           
          if (record < total_records) {
            current++;
            restore_course();
          }else {
            $('.progress').hide();
            $('.result').html(data.result);
          }
        },
        error: function() {
          window.console.log('Error on record ' + record);
          record_row.removeClass('border-primary');
          record_row.removeClass('text-success');
          record_row.removeClass('text-primary');
          record_row.addClass('text-danger');

          var currentProgress = Math.floor((current / total_records) * 100);
          $('.progress-bar').attr('aria-valuenow', currentProgress);
          $('.progress-bar').attr('style', 'width: ' + currentProgress + '%');
          $('.progress-bar').html(currentProgress + '%');

          if (record <= total_records) {
            current++;
            restore_course();
          }else {
            $('.progress').hide();
          }
        }
      });
    }
  };


  return  {
    init: function() {
      $('#restore_courses').on('click', function() {
        restore_courses();
      });
    },
  };
});
