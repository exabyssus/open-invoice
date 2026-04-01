<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept Invitation</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; width: 100%; max-width: 420px; }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .subtitle { color: #6b7280; margin-bottom: 1.5rem; font-size: 0.9rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: #374151; }
        input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1rem; }
        input:focus { outline: none; border-color: #06b6d4; box-shadow: 0 0 0 2px rgba(6,182,212,0.2); }
        input[readonly] { background: #f9fafb; color: #6b7280; }
        button { width: 100%; padding: 0.625rem; background: #06b6d4; color: white; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer; }
        button:hover { background: #0891b2; }
        .error { color: #dc2626; font-size: 0.8rem; margin-top: -0.75rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Join {{ $invitation->company->title }}</h1>
        <p class="subtitle">Set up your account to accept the invitation.</p>

        <form method="POST" action="/panel/invitation/{{ $token }}/register">
            @csrf
            <label for="email">Email</label>
            <input type="email" value="{{ $invitation->email }}" readonly>

            <label for="name">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus>
            @error('name') <p class="error">{{ $message }}</p> @enderror

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <label for="password_confirmation">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>

            <button type="submit">Create Account & Join</button>
        </form>
    </div>
</body>
</html>
