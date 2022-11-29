

document.onreadystatechange = function () {
  if (document.readyState == "complete") {
    // console.log('document is ready. I can sleep now');
    // var container = document.getElementById('block-views-block-affinity-group-group-2')
    // console.log('container = ' + container)

    const ciderIds = drupalSettings.ciderIds
    if (ciderIds.length > 0) showAgOutages(ciderIds)
  }
}

const showAgOutages = async function showAgOutages(ciderIds) {
  
  let outagesDiv = document.createElement('div')
  outagesDiv.innerHTML = `<br>
    <div class="outage-list section container">
      <div class="row">
        <div class="mb-3">
          <h3 class="border-bottom pb-2">Planned Downtimes</h3>
          <p id="no-planned-outages">(Retrieving planned outages scheduled for the Associated Infrastructure)</p>
          <div class="table-responsive">
            <table id="outages-planned" class="display text-start table" style="display:none;">
              <thead>
                <tr>
                  <th>Event</th>
                  <th>Start</th>
                  <th>End</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    `

  var container = document.getElementById('access_news')
  container.appendChild(outagesDiv, container.firstChild);

  showOutages(ciderIds, 'planned')
}


const showOutages = async function showOutages(ciderIds, outageName) {

  
  // const endpointUrl = 'https://info.xsede.org/wh1/outages/v1/outages/Future'

  // for testing, get all outages
  const endpointUrl = 'https://info.xsede.org/wh1/outages/v1/outages'

  const response = await fetch(endpointUrl)
  let outages = await response.json()

  let filtered = []
  
  Object.keys( outages ).forEach( function( key ) {
    if(ciderIds.indexOf(outages[key]['ResourceID']) > -1) {
        filtered.push(outages[key]);
    }
  });

  const noOutages = document.getElementById(`no-${outageName}-outages`)
  if (filtered.length == 0) {
    noOutages.innerHTML = 'There are no ' + outageName + ' outages for Associated Infrastructure' 
  } else {
    noOutages.style.display = 'none' 
    const outagesTable = document.getElementById('outages-' + outageName)
    const options = {
      timeZoneName: 'short'
    }

    jQuery(outagesTable).DataTable({
      data: filtered,
      columns: [
        { data: 'Subject',
          render: function ( data, type, row, meta ) {
            return type === 'display' ? `<a href="https://support.access-ci.org/outages?outageID=${row['ID']}">${data}</a>` : data;
          }  
        },
        { data: 'OutageStart',
          render: function ( data, type, row, meta ) {
            return type === 'display' ? new Date(data).toLocaleString(navigator.language, options) : data;
          } 
        },
        { data: 'OutageEnd',
          render: function ( data, type, row, meta ) {
            return type === 'display' ? new Date(data).toLocaleString(navigator.language, options) : data;
          }
        }
      ],
      order: [[1, 'desc']], // sort by OutageStart
      bPaginate: false,
      bAutoWidth: false
    })
    outagesTable.style.display = 'block'
  }
}
