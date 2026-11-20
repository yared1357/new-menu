</div> <!-- End of content padding div -->

<footer class="admin-footer fixed bottom-0 left-0 right-0 z-30 border-t border-white/10 bg-gradient-to-r from-[#1f322e] via-[#153b34] to-[#3a2314] px-4 text-white shadow-[0_-10px_30px_rgba(16,39,32,0.12)] sm:px-8 lg:px-12">
    <div class="flex h-full flex-col items-center justify-center gap-2 sm:flex-row sm:justify-between sm:gap-4">
        <div class="text-center sm:text-left">
            <p class="text-xs font-semibold tracking-wide text-white sm:text-sm"><?= htmlspecialchars(get_app_setting('brand_name', 'Tasty Pizza & Burger')) ?></p>
           </div>
        <div class="flex flex-wrap items-center justify-center gap-2 text-[9px] font-bold uppercase tracking-[0.12em] sm:justify-end sm:gap-3 sm:text-[10px] sm:tracking-[0.16em]">
            <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1.5 text-orange-100/80 sm:px-3">System v1.0</span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/20 bg-emerald-400/10 px-2.5 py-1.5 text-emerald-100 sm:gap-2 sm:px-3">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                Operational
            </span>
            <a href="../index.php" class="rounded-full border border-white/20 px-2.5 py-1.5 text-white/85 transition-colors hover:border-white/40 hover:bg-white/10 hover:text-white sm:px-3">View site</a>
        </div>
    </div>
</footer>
</main>
</div>
</body>

</html>