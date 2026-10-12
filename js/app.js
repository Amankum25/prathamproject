/*
 * RC Campus Connect · Module P27: Bill Tracking System
 * js/app.js — Unit 5 jQuery & AJAX interactions.
 */
$(function () {
  function esc(s) { return $('<div>').text(s).html(); }

  // -------------------------------------------------------------------
  // 1. Live Ledger Filter & Search (index.php)
  // -------------------------------------------------------------------
  var $q = $('#q'), $status = $('#status'), $dept = $('#department_id'), timer = null;

  function doSearch() {
    $.getJSON(RC.base + 'api/search.php', {
      q: $q.val(),
      status: $status.val(),
      department_id: $dept.val()
    }, function (data) {
      var html = $.map(data.rows, function (r) {
        return '<tr>' +
          '<td class="mono small"><b>' + esc(r.ref) + '</b><div class="muted small">Inv: ' + esc(r.inv_no) + '</div></td>' +
          '<td><b>' + esc(r.vendor) + '</b><div class="mono small muted">' + esc(r.gstin) + '</div></td>' +
          '<td>' + esc(r.department) + '</td>' +
          '<td><b>Rs ' + esc(r.amount) + '</b></td>' +
          '<td>' + esc(r.date) + '</td>' +
          '<td>' + r.badge + '<div class="small muted" style="margin-top:2px">📍 ' + esc(r.current_desk) + '</div></td>' +
          '<td class="actions"><a class="btn btn-sm btn-outline" href="' + esc(RC.base + r.path) + '">Workflow</a></td>' +
        '</tr>';
      }).join('');

      $('#rows').html(html);
      $('#none').remove();
      if (data.count === 0) {
        $('#rows').closest('.table-wrap').append('<div class="empty" id="none">No bills match your criteria.</div>');
      }

      if (data.counts) {
        $('[data-count="received"]').text(data.counts.received || 0);
        $('[data-count="processing"]').text((data.counts.processing || 0) + (data.counts.verified || 0));
        $('[data-count="paid"]').text(data.counts.paid || 0);
      }
    });
  }

  if ($('#filter').length) {
    $q.on('input', function () {
      clearTimeout(timer);
      timer = setTimeout(doSearch, 250);
    });
    $status.on('change', doSearch);
    $dept.on('change', doSearch);
  }

  // -------------------------------------------------------------------
  // 2. Live Bill Movement Tracker (track.php)
  // -------------------------------------------------------------------
  var $trackQ = $('#track_q'), $trackBtn = $('#track_btn'), $trackResults = $('#track_results');

  function doTrack(ref) {
    if (!ref) return;
    $trackBtn.prop('disabled', true).text('Loading…');
    $trackResults.html('<div style="text-align:center; padding:24px;" class="muted"><span class="spinner"></span> Querying desk movements for ' + esc(ref) + '…</div>');

    $.getJSON(RC.base + 'api/search.php', { track_no: ref }, function (res) {
      $trackBtn.prop('disabled', false).text('🔍 Track');

      if (!res.found) {
        $trackResults.html('<div class="empty" style="color:var(--bad)"><h3>' + esc(res.message) + '</h3><p class="muted">Check the bill number and try again.</p></div>');
        return;
      }

      var movHtml = '';
      if (!res.movements || res.movements.length === 0) {
        movHtml = '<p class="muted">No movement records recorded.</p>';
      } else {
        movHtml += '<div class="table-wrap"><table class="table small"><thead><tr><th>From &rarr; To Desk</th><th>Action</th><th>Officer</th><th>Date & Time</th></tr></thead><tbody>';
        $.each(res.movements, function (i, m) {
          movHtml += '<tr>' +
            '<td><b>' + esc(m.from_desk) + '</b> &rarr; <b>' + esc(m.to_desk) + '</b>' +
            (m.remarks ? '<div class="muted small" style="margin-top:2px">💬 <em>"' + esc(m.remarks) + '"</em></div>' : '') +
            '</td>' +
            '<td>' + m.action_bad + '</td>' +
            '<td>' + esc(m.officer) + '</td>' +
            '<td>' + esc(m.time) + '</td>' +
          '</tr>';
        });
        movHtml += '</tbody></table></div>';
      }

      var card = '<div class="card">' +
        '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; border-bottom:1px solid var(--line); padding-bottom:12px;">' +
          '<div>' +
            res.badge +
            '<h2 style="margin:6px 0 2px 0;" class="mono">' + esc(res.bill_no) + '</h2>' +
            '<div class="muted small">Vendor: <b>' + esc(res.vendor_name) + '</b> (' + esc(res.vendor_gstin) + ') · Inv: ' + esc(res.vendor_inv) + '</div>' +
          '</div>' +
          '<div style="text-align:right;">' +
            '<div style="font-size:20px; font-weight:700; color:var(--maroon)">Rs ' + esc(res.amount) + '</div>' +
            '<div class="small muted">Dated: ' + esc(res.bill_date) + '</div>' +
          '</div>' +
        '</div>' +
        '<div style="background:var(--bg); padding:10px 14px; border-radius:4px; margin-bottom:16px;">' +
          '<div class="small muted" style="text-transform:uppercase; font-weight:600;">Amount in Words:</div>' +
          '<div style="font-style:italic; font-size:13px; color:var(--text);">"' + esc(res.amount_words) + '"</div>' +
        '</div>' +
        '<div class="stat" style="margin-bottom:16px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center;">' +
          '<div><strong>Current Desk Location:</strong> 📍 ' + esc(res.current_desk) + '</div>' +
          '<a class="btn btn-sm" href="' + esc(RC.base + res.path) + '">Open Workflow &rarr;</a>' +
        '</div>' +
        '<h3>Desk-to-Desk Handover Trail</h3>' +
        movHtml +
      '</div>';

      $trackResults.html(card);
    }).fail(function () {
      $trackBtn.prop('disabled', false).text('🔍 Track');
      $trackResults.html('<div class="empty" style="color:var(--bad)"><h3>Error querying server</h3><p class="muted">Check your network connection and server status.</p></div>');
    });
  }

  if ($('#track_form').length) {
    $('#track_btn').on('click', function () { doTrack($.trim($trackQ.val())); });
    $trackQ.on('keypress', function (e) {
      if (e.which === 13) { doTrack($.trim($trackQ.val())); }
    });
    $('.quick-track').on('click', function () {
      var ref = $(this).data('ref');
      $trackQ.val(ref);
      doTrack(ref);
    });
  }
});
