@php
    $mailer = config('mail.default');
    $smtpPass = config('mail.mailers.smtp.password');
@endphp
@if (in_array($mailer, ['log', 'array'], true))
    <div class="kfc-alert-warn mt-4 text-sm" role="status">
        <p class="font-semibold">メールは受信トレイに届きません（現在の設定）</p>
        <p class="mt-2 leading-relaxed">
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_MAILER={{ $mailer }}</code>
            のとき、送信内容はログに記録されるだけです。実際にメールで送るには
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">.env</code>
            で
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_MAILER=smtp</code>
            と
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_HOST</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_PORT</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_USERNAME</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_PASSWORD</code>・<code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_FROM_ADDRESS</code>
            を、利用するメールサービス（ConoHa メール・社内SMTP・SendGrid 等）の値に合わせて設定してください。送信後は
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">storage/logs/laravel.log</code>
            に本文が残っているか確認できます。
        </p>
    </div>
@elseif ($mailer === 'smtp' && empty($smtpPass))
    <div class="kfc-alert-warn mt-4 text-sm" role="status">
        <p class="font-semibold">メールは送信されません（SMTP パスワード未設定）</p>
        <p class="mt-2 leading-relaxed">
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_MAILER=smtp</code>
            ですが <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">MAIL_PASSWORD</code> が空です。ConoHa の場合はコントロールパネルで
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">{{ config('mail.from.address') }}</code>
            のメールアドレスを作成し、そのパスワードを <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">.env</code> に設定したあと
            <code class="rounded bg-amber-100/80 px-1 py-0.5 text-xs text-zinc-900">php artisan config:clear</code>
            を実行してください。
        </p>
    </div>
@endif
