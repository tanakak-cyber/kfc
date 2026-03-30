@if (in_array(config('mail.default'), ['log', 'array'], true))
    <div class="kfc-alert-warn mt-4 text-sm" role="status">
        <p class="font-semibold">メールは受信トレイに届きません（現在の設定）</p>
        <p class="mt-2 leading-relaxed">
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_MAILER={{ config('mail.default') }}</code>
            のとき、送信内容はログに記録されるだけです。実際にメールで送るには
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">.env</code>
            で
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_MAILER=smtp</code>
            と
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_HOST</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_PORT</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_USERNAME</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_PASSWORD</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_FROM_ADDRESS</code>
            を、利用するメールサービス（社内SMTP・Gmailアプリパスワード・SendGrid 等）の値に合わせて設定してください。送信後は
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">storage/logs/laravel.log</code>
            に本文が残っているか確認できます。
        </p>
    </div>
@endif
