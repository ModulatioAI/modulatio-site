// The bug-report form on the Caesar page, sent without leaving the page: the answer is shown
// under the button. Without this script the form still posts, and the server answers with a page.
const form = document.querySelector('.caesar-bugform');
if (form) {
	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		const said = form.querySelector('.caesar-bugform__said');
		const button = form.querySelector('button');
		const data = new FormData(form);
		data.set('js', '1');
		button.disabled = true;
		said.textContent = 'Sending...';
		try {
			const reply = await fetch(form.action, { method: 'POST', body: data });
			const got = await reply.json();
			said.textContent = got.message;
			if (got.ok) {
				form.reset();
			} else {
				button.disabled = false;
			}
		} catch {
			said.textContent = 'The report could not be sent. Please check your connection and try again.';
			button.disabled = false;
		}
	});
}
