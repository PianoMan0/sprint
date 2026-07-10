console.log("Sprint UI loaded");

document.addEventListener('DOMContentLoaded', function () {
	const search = document.getElementById('event-search');
	const grid = document.querySelector('.card-grid');
	if (!search || !grid) return;

	const noResults = document.createElement('div');
	noResults.className = 'no-results';
	noResults.textContent = 'No events found';
	noResults.style.padding = '1rem';
	noResults.style.color = 'var(--muted)';
	noResults.style.display = 'none';
	noResults.setAttribute('role', 'status');
	noResults.setAttribute('aria-live', 'polite');
	grid.parentNode.insertBefore(noResults, grid.nextSibling);

	const cards = () => Array.from(grid.querySelectorAll('.card'));

	search.addEventListener('input', function (e) {
		const q = (e.target.value || '').trim().toLowerCase();
		let any = false;
		cards().forEach(card => {
			const text = card.textContent.toLowerCase();
			const match = q === '' || text.indexOf(q) !== -1;
			card.style.display = match ? '' : 'none';
			if (match) any = true;
		});
		noResults.style.display = any ? 'none' : '';
	});
});

