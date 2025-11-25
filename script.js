// Support Form Handler (example; you can modify as needed)
// Keep the default form submit behavior so the browser sends the filled form
// Do NOT reset the form here (that would clear fields before submit).
document.getElementById('supportForm').addEventListener('submit', function(e) {
  // Let the form submit normally to the server-side script.
  // Server will respond (and can show a success message). If you prefer
  // AJAX submission instead, we can change this to a fetch/XHR flow.
});

// Chatbot UI Handler - Just basic display, you design logic
const chatbotForm = document.getElementById('chatbot-form');
const chatbotInput = document.getElementById('chatbot-input');
const chatbotMessages = document.getElementById('chatbot-messages');
const secondaryOptions = {
  battery: [
    "Dies quickly",
    "Does not charge",
    "Something else"
  ],
  screen: [
    "Cracked",
    "Black screen",
    "Flickering display",
    "Something else"
  ],
  keyboard: [
    "Key(s) stuck",
    "Not typing",
    "Keys missing",
    "Something else"
  ],
  wifi: [
    "Can't connect",
    "Drops connection",
    "Slow speeds",
    "Something else"
  ],
  login: [
    "Forgot password",
    "Account locked",
    "Something else"
  ],
  other: [
    "Something else"
  ]
};

const problemSelect = document.getElementById('problem');
const secondaryContainer = document.getElementById('secondary-container');

problemSelect.addEventListener('change', function() {
  secondaryContainer.innerHTML = '';
  const selected = this.value;
  if (secondaryOptions[selected]) {
    // Create secondary dropdown
    const label = document.createElement('label');
    label.textContent = "More details:";
    label.setAttribute('for', 'subproblem');
  
    const secondary = document.createElement('select');
    secondary.setAttribute('id', 'subproblem');
    secondary.name = 'subproblem';
    secondary.required = true;
    secondary.innerHTML = `<option value="" disabled selected>Select...</option>` +
      secondaryOptions[selected].map(opt => `<option value="${opt.toLowerCase().replace(/\s+/g,'_')}">${opt}</option>`).join('');
    
    secondaryContainer.appendChild(label);
    secondaryContainer.appendChild(secondary);

    // Show/hide textbox based on "Something else"
    secondary.addEventListener('change', function() {
      let textbox = document.getElementById('custom-detail');
      if (textbox) textbox.remove();

      if (this.value === 'something_else') {
        const tb = document.createElement('input');
        tb.type = 'text';
        tb.id = 'custom-detail';
        tb.name = 'custom-detail';
        tb.placeholder = 'Please specify...';
        tb.required = true;
        tb.style.display = 'block';
        tb.style.marginTop = '8px';
        secondaryContainer.appendChild(tb);
      }
    });
  }
});



chatbotForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const msg = chatbotInput.value.trim();
  if(msg) {
    const userBubble = document.createElement('div');
    userBubble.textContent = msg;
    userBubble.style.marginBottom = '4px';
    userBubble.style.textAlign = 'right';
    userBubble.style.color = '#7b1fa2';
    chatbotMessages.appendChild(userBubble);
    chatbotInput.value = '';
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    // You: insert your chatbot response logic here
  }
});
