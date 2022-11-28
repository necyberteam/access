/* will need outagesId */
console.log('--hi--')
// console.log(drupalSettings.ciderIds)


// document.body.onload = addElement(outages);

function addElement(outages) {

  outage=outages[0];
  console.log('in addElement')
  console.log(outage)

  // create a new div element
  const newDiv = document.createElement('div');

  // and give it some content
  const newContent = document.createTextNode('Hi there and greetings!');
  // const newContent2 = document.createTextNode('<br><br>outage: ' + outages[0]);

  const table_string = 
'<div class="outage-list section container">             \
<div class="row">             \
  <div class="col text-start text-md-center">         \
    <h2>All Outages</h2>              \
    <p id="no-past-outages">Loading past outages...</p>               \
    <div class="table-responsive">            \
      <table id="outages-past" class="display text-start table">              \
          <thead>             \
              <tr>            \
                  <th>Event</th>              \
                  <th>Resource</th>           \
                  <th>Summary</th>            \
                  <th>Type</th>               \
                  <th>Start</th>              \
                  <th>End</th>                \
              </tr>           \
          </thead>            \
          <tbody>             \
          </tbody>            \
      </table>                \
    </div>            \
  </div>              \
</div>                \
</div>          \
';
  // add the text node to the newly created div
  newDiv.appendChild(newContent);
  // newDiv.appendChild(newContent2);
  newDiv.appendChild(table_string);

  // add the newly created element and its content into the DOM
  const currentDiv = document.getElementById("div1");
  document.body.insertBefore(newDiv, currentDiv);
}


const showAgOutage = async function showAgOutage(ciderId) {
  console.log('inside showAgOutage(), ciderId = ' + ciderId)

  // the actual endpoint is something like following
  // const response = await fetch(`https://info.xsede.org//wh1/outages/v1/outages/StatusRelevant/ResourceID/acf.nics.xsede.org`)
  // 
  //                               https://info.xsede.org/wh1/outages/v1/outages/StatusRelevant/ResourceID/{ResourceID}/
  // const response = await fetch(`https://info.xsede.org/wh1/outages/v1/outages/ID/${outageID}`)

  // for testing, get all past outages
  const response = await fetch(`https://info.xsede.org/wh1/outages/v1/outages/`)
  const outages = await response.json()

  outage=outages[0];

  console.log('into outage')
  console.log(outage)
  // console.log(outage['Content'])

  // var json = JSON.parse(outage)
  // console.log(json.Content)

  
  // create a new div element
  const newDiv = document.createElement("div");

  // and give it some content
  // const newContent = document.createTextNode("Hi #2 !<br><br>" + outage.Content);
  // const newContent2 = document.createTextNode("<br><br>outage: " + outages[0]);

  
  // newDiv.appendChild(newContent);

  // add the newly created element and its content into the DOM
  // const currentDiv = document.getElementById("div1");
  // document.body.insertBefore(newDiv, currentDiv);

  // Create table title

  var outageElement = document.createElement('div')

  var tableIntro = document.createElement('h3')
  var tableIntroText = document.createTextNode('Will be a table of Outages for ' + ciderId)
  tableIntro.appendChild(tableIntroText)
  outageElement.appendChild(tableIntro)

  var outageList = document.createElement('div')
  outageList.setAttribute('class', 'outage-list section container')
  outageElement.appendChild(outageList)

  var row = document.createElement('div')
  row.setAttribute('class', 'row')
  outageList.appendChild(row)

  var col = document.createElement('div')
  col.setAttribute('class', 'col text-start')
  row.appendChild(col)

  var responsive = document.createElement('div')
  responsive.setAttribute('class', 'table-responsive')
  col.appendChild(responsive)

  var outagesTable = document.createElement('table')
  outagesTable.setAttribute('id', 'outages-table')
  outagesTable.setAttribute('class', 'text-start table')
  responsive.appendChild(outagesTable)lan

  var thead = document.createElement('thead')
  outagesTable.appendChild(thead)

  var headerTr = document.createElement('tr')
  thead.appendChild(headerTr)

  var tableTh = document.createElement('th')
  headerTr.appendChild(tableTh)

  var thText = document.createTextNode('Resource')
  tableTh.appendChild(thText)

  var tableTh = document.createElement('th')
  headerTr.appendChild(tableTh)

  var thText = document.createTextNode('Summary')
  tableTh.appendChild(thText)
  
  var tbody = document.createElement('tbody')
  outagesTable.appendChild(tbody)

  var bodyTr = document.createElement('tr')
  bodyTr.setAttribute('class', 'odd')
  tbody.appendChild(bodyTr)

  var bodyTd = document.createElement('td')
  bodyTr.appendChild(bodyTd)

  var tdText = document.createTextNode(outage['ResourceID']);
  bodyTd.appendChild(tdText)

  var bodyTd = document.createElement('td')
  bodyTr.appendChild(bodyTd)

  var tdPara = document.createElement('p')
  bodyTd.appendChild(tdPara)

  var tdText = document.createTextNode(outage['Content']);
  tdPara.appendChild(tdText)










  
  // for(var i=0; i<5; i++)
  // {
  //     var newP = document.createElement('h4')
  //     var text = document.createTextNode('new paragraph number: ' + i)
  //     newP.appendChild(text)
  //     outageElement.appendChild(newP)

  //     // container.insertBefore(newP, container.firstChild);
  // }


  // var tableTitle = document.createTextNode('a text node')

  // // outageElement.insertBefore(a, container.firstChild);
  // xxx.appendChild(tableTitle)

  
  // Create table object.
  // <div class='table-responsive'>

  // document.body.appendChild(a);
  // col.appendChild(outagesTable)


  // var b = document.createElement('TR');
  // b.setAttribute('id', 'MyTr');
  // a.appendChild(b);

  // var c = document.createElement('TD');
  // var d = document.createElement('p')
  // var e = document.createTextNode(outage['Content']);
  // d.appendChild(e);
  // c.appendChild(d);
  // b.appendChild(c);


  var container = document.getElementById('block-views-block-affinity-group-group-2')
  container.insertBefore(outageElement, container.firstChild);

  // return outages

}

var ciderId = drupalSettings.ciderIds[0]

showAgOutage(ciderId)


