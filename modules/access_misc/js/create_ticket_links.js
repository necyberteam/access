// Update additional ticket buttons with the custom url

let ticket_btn = document.querySelector('.block-create-ticket-button .btn');
let btns = document.querySelectorAll('.open-a-ticket.btn');

btns.forEach( btn => {
  btn.href = ticket_btn.href;
  btn.innerHTML = "Create a Ticket";
});
