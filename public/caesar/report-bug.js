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
		// The same limits the server keeps, said before anything is sent.
		const files = [...(form.querySelector('input[type=file]')?.files ?? [])];
		const total = files.reduce((sum, f) => sum + f.size, 0);
		const tooBig = files.find((f) => f.size > 10 * 1024 * 1024);
		const refusal = files.length > 3 ? 'Please send at most 3 files with a report.'
			: tooBig ? `${tooBig.name} is larger than 10 MB. Please send a smaller one, or put it in a zip.`
			: total > 20 * 1024 * 1024 ? 'The files come to more than 20 MB together. Please send fewer, or put them in a zip.'
			: '';
		if (refusal) {
			said.textContent = refusal;
			return;
		}
		button.disabled = true;
		said.textContent = 'Sending...';
		try {
			const reply = await fetch(form.action, { method: 'POST', body: data, headers: { 'X-Caesar-Form': '1' } });
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
