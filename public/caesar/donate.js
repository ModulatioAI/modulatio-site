// The copy buttons beside the donation addresses: the address named by data-copy goes to the clipboard.
document.querySelectorAll('[data-copy]').forEach((button) => {
	button.addEventListener('click', () => {
		const words = document.getElementById(button.dataset.copy).textContent.trim();
		navigator.clipboard.writeText(words).then(() => {
			button.textContent = 'COPIED';
			setTimeout(() => (button.textContent = 'COPY'), 2000);
		});
	});
});
