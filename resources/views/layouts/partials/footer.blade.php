<footer class="footer">
    <div class="container footer-content">
        <div class="footer-left">
            <p>&copy; {{ date('Y') }} RIPAIR &mdash; Tous droits r&eacute;serv&eacute;s.</p>
            <p class="footer-meta">EI RIPAIR &middot; 06&nbsp;15&nbsp;58&nbsp;87&nbsp;82 &middot; contact@ripair.shop</p>
        </div>
        <div class="footer-links" style="justify-content:center; gap:20px; text-align:center;">
            <a href="{{ route('legal.mentions') }}">Mentions l&eacute;gales</a>
            <a href="{{ route('legal.cgv') }}">Conditions g&eacute;n&eacute;rales de vente</a>
            <a href="{{ route('catalog.index') }}">Catalogue</a>
        </div>
        <div class="footer-right">
            <a href="https://instagram.com/ripair.pessac" target="_blank" rel="noopener">
                <img src="{{ asset('assets/img/instagram.webp') }}" alt="Instagram" class="social-icon">
            </a>
            <a href="https://www.facebook.com/profile.php?id=61579654974480" target="_blank" rel="noopener">
                <img src="{{ asset('assets/img/facebook.webp') }}" alt="Facebook" class="social-icon">
            </a>
            <a href="https://ripair.shop/contact.html" class="btn-contact">Contactez-nous</a>
        </div>
    </div>
</footer>
