@extends('layouts.app')

@section('title', 'Dashboard — Daily Win Board')

@section('content')
{{-- Streak + Wins header bar --}}
<div class="flex items-center gap-4 mb-6">
    <div class="card flex items-center gap-3 py-3 px-5" style="box-shadow: var(--shadow-hard-sm);">
        <span class="text-2xl">🔥</span>
        <div>
            <div class="font-mono font-bold text-xl">{{ $stat->streak }}</div>
            <div class="text-xs text-[#6B6B6B] uppercase tracking-wide">Day Streak</div>
        </div>
    </div>
    <div class="card flex items-center gap-3 py-3 px-5" style="box-shadow: var(--shadow-hard-sm);">
        <span class="text-2xl">✅</span>
        <div>
            <div class="font-mono font-bold text-xl" id="wins-count">{{ $winsToday }}</div>
            <div class="text-xs text-[#6B6B6B] uppercase tracking-wide">Wins Today</div>
        </div>
    </div>
</div>

{{-- Daily Commitment --}}
<div class="card mb-6" style="box-shadow: var(--shadow-hard);">
    <div class="text-xs font-bold uppercase tracking-widest text-[#6B6B6B] mb-3">Your one non-negotiable today</div>
    <div id="commit-locked" class="{{ $commit && $commit->isLocked() ? '' : 'hidden' }}">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🔒</span>
                <span class="font-display font-bold text-xl" id="commit-text">{{ $commit?->text }}</span>
            </div>
            @if($commit && $commit->canUnlock())
                <button onclick="unlockCommit()" class="btn text-sm" style="padding: 6px 14px;">Edit</button>
            @endif
        </div>
    </div>
    <div id="commit-form" class="{{ $commit && $commit->isLocked() ? 'hidden' : '' }}">
        <div class="flex gap-3">
            <input type="text"
                   id="commit-input"
                   class="input flex-1 text-lg font-medium"
                   placeholder="What is your ONE non-negotiable task today?"
                   value="{{ $commit && !$commit->isLocked() ? $commit->text : '' }}"
                   maxlength="500">
            <button onclick="lockCommit()" class="btn whitespace-nowrap" style="padding: 10px 24px;">
                🔒 Lock it in
            </button>
        </div>
    </div>
</div>

{{-- Task Board 2×2 grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
    @php
        $sections = [
            'must'   => ['label' => 'Must Do Today',   'color' => '#FF4F00', 'limit' => 3, 'emoji' => '🔴'],
            'should' => ['label' => 'Should Do Today',  'color' => '#FFC900', 'limit' => 5, 'emoji' => '🟡'],
            'good'   => ['label' => 'Good To Do',       'color' => '#23A094', 'limit' => null, 'emoji' => '🟢'],
            'park'   => ['label' => 'Parking Lot',      'color' => '#B0B0A8', 'limit' => null, 'emoji' => '🅿️'],
        ];
    @endphp

    @foreach($sections as $key => $meta)
        @php
            $sectionTasks = $tasks[$key] ?? collect();
            $activeCount = $sectionTasks->where('done', false)->count();
        @endphp
        <div class="card" style="box-shadow: var(--shadow-hard); border-left: 4px solid {{ $meta['color'] }}; padding-left: 1rem;">
            {{-- Section header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="font-display font-extrabold text-[13px] uppercase tracking-wider">{{ $meta['label'] }}</span>
                    <span class="bg-black text-white text-[11px] font-bold px-2 py-0.5 rounded-[3px] font-mono" id="count-{{ $key }}">
                        {{ $activeCount }}{{ $meta['limit'] ? '/'.$meta['limit'] : '' }}
                    </span>
                </div>
            </div>

            {{-- Task list --}}
            <ul class="space-y-2 mb-4 min-h-[60px]" id="task-list-{{ $key }}">
                @forelse($sectionTasks as $task)
                    <li class="task-item flex items-center gap-3 group {{ $task->done ? 'opacity-50' : '' }}" data-id="{{ $task->id }}" data-section="{{ $key }}">
                        <button onclick="toggleTask({{ $task->id }}, this)" class="flex-shrink-0 w-5 h-5 border-2 border-black rounded-[3px] flex items-center justify-center cursor-pointer hover:bg-black/10 transition-colors {{ $task->done ? 'bg-black' : '' }}">
                            @if($task->done)
                                <svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" fill="none"/></svg>
                            @endif
                        </button>
                        <span class="flex-1 text-[15px] {{ $task->done ? 'line-through text-[#6B6B6B]' : '' }}">{{ $task->text }}</span>
                        @if(($task->days_late ?? 0) >= 2)
                            <span class="text-[10px] font-bold bg-[#FF4F00] text-white px-2 py-0.5 rounded-[3px] shrink-0">🚨 URGENT</span>
                        @elseif(($task->days_late ?? 0) === 1)
                            <span class="text-[10px] font-bold bg-[#FFC900] text-black px-2 py-0.5 rounded-[3px] shrink-0">⚠️ LATE</span>
                        @endif
                        @if($key === 'park')
                            <button onclick="promoteTask({{ $task->id }}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-xs font-bold border border-black rounded px-2 py-1 hover:bg-black hover:text-white transition-all">→ Tomorrow</button>
                        @endif
                        @if($key !== 'must' && !$task->done)
                            <div class="relative move-wrap">
                                <button onclick="toggleMoveMenu(event, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-xs font-bold border border-black rounded px-2 py-1 hover:bg-black hover:text-white transition-all">⇄</button>
                                <div class="move-dropdown hidden absolute right-0 top-full mt-1 bg-white border-2 border-black z-20 text-xs font-bold" style="box-shadow: 2px 2px 0 #000;">
                                    @foreach($sections as $targetKey => $targetMeta)
                                        @if($targetKey !== $key && $targetKey !== 'must')
                                            <button onclick="moveTask({{ $task->id }}, '{{ $targetKey }}', this)" class="block w-full text-left px-3 py-2 hover:bg-black hover:text-white whitespace-nowrap">{{ $targetMeta['emoji'] }} {{ $targetMeta['label'] }}</button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <button onclick="deleteTask({{ $task->id }}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>
                    </li>
                @empty
                    <li class="task-empty text-[#B0B0A8] text-sm border-2 border-dashed border-[#B0B0A8] rounded-[6px] p-4 text-center">
                        @if($key === 'must') Pick your top 3 — no more.
                        @elseif($key === 'should') What should get done today?
                        @elseif($key === 'good') Nice to haves go here.
                        @else Ideas parked for later.
                        @endif
                    </li>
                @endforelse
            </ul>

            {{-- Add task input --}}
            <div class="flex gap-2" id="add-{{ $key }}-wrap">
                <input type="text"
                       id="add-{{ $key }}"
                       class="input text-sm"
                       placeholder="Add task..."
                       onkeydown="if(event.key==='Enter') addTask('{{ $key }}')">
                <button onclick="addTask('{{ $key }}')" class="btn text-sm" style="padding: 8px 14px; white-space: nowrap;">+</button>
            </div>
            <div id="error-{{ $key }}" class="hidden text-[#E24B4A] text-xs font-bold mt-2"></div>
        </div>
    @endforeach
</div>

{{-- Reset day --}}
<div class="text-center">
    <button onclick="showResetModal()" class="text-sm text-[#6B6B6B] hover:text-black font-medium underline underline-offset-2">Reset today's tasks</button>
</div>

{{-- Reset modal --}}
<div id="reset-modal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
    <div class="card max-w-sm w-full" style="box-shadow: 6px 6px 0 #000;">
        <h3 class="font-display font-extrabold text-xl mb-2">Reset today?</h3>
        <p class="text-[#6B6B6B] text-[14px] mb-6">This clears all tasks and your commitment for today. Cannot be undone.</p>
        <div class="flex gap-3">
            <button onclick="hideResetModal()" class="btn flex-1 justify-center">Cancel</button>
            <button onclick="confirmReset()" class="btn btn-danger flex-1 justify-center">Yes, reset</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const toastMessages = ['Done! Keep going 🔥', 'Yes! That counts!', 'One more win!', 'You showed up.', 'Progress! ⭐'];
const sectionLabels = {
    should: '🟡 Should Do Today',
    good:   '🟢 Good To Do',
    park:   '🅿️ Parking Lot',
};
const moveTargets = {
    should: ['good', 'park'],
    good:   ['should', 'park'],
    park:   ['good', 'should'],
};

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg || toastMessages[Math.floor(Math.random() * toastMessages.length)];
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 1500);
}

async function lockCommit() {
    const input = document.getElementById('commit-input');
    const text = input.value.trim();
    if (!text) return;

    const res = await fetch('{{ route("commit.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ text })
    });
    const data = await res.json();
    if (!res.ok) { alert(data.error); return; }

    document.getElementById('commit-text').textContent = text;
    document.getElementById('commit-locked').classList.remove('hidden');
    document.getElementById('commit-form').classList.add('hidden');
    showToast('Commitment locked! 🔒');
}

async function unlockCommit() {
    const res = await fetch('{{ route("commit.unlock") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (!res.ok) { const d = await res.json(); alert(d.error); return; }
    document.getElementById('commit-locked').classList.add('hidden');
    document.getElementById('commit-form').classList.remove('hidden');
    document.getElementById('commit-input').value = document.getElementById('commit-text').textContent;
}

async function addTask(section) {
    const input = document.getElementById('add-' + section);
    const errorEl = document.getElementById('error-' + section);
    const text = input.value.trim();
    if (!text) return;

    errorEl.classList.add('hidden');

    const res = await fetch('{{ route("tasks.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ text, section })
    });
    const data = await res.json();

    if (!res.ok) {
        errorEl.textContent = data.error;
        errorEl.classList.remove('hidden');
        return;
    }

    input.value = '';
    appendTask(data, section);
    updateCount(section);
    removePlaceholder(section);
}

function appendTask(task, section) {
    const list = document.getElementById('task-list-' + section);
    const li = document.createElement('li');
    li.className = 'task-item flex items-center gap-3 group';
    li.dataset.id = task.id;
    li.dataset.section = section;

    const promoteBtn = section === 'park'
        ? `<button onclick="promoteTask(${task.id}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-xs font-bold border border-black rounded px-2 py-1 hover:bg-black hover:text-white transition-all">→ Tomorrow</button>`
        : '';

    const moveOptions = (moveTargets[section] || [])
        .map(k => `<button onclick="moveTask(${task.id}, '${k}', this)" class="block w-full text-left px-3 py-2 hover:bg-black hover:text-white whitespace-nowrap">${sectionLabels[k]}</button>`)
        .join('');
    const moveBtn = section !== 'must' ? `
        <div class="relative move-wrap">
            <button onclick="toggleMoveMenu(event, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-xs font-bold border border-black rounded px-2 py-1 hover:bg-black hover:text-white transition-all">⇄</button>
            <div class="move-dropdown hidden absolute right-0 top-full mt-1 bg-white border-2 border-black z-20 text-xs font-bold" style="box-shadow:2px 2px 0 #000;">${moveOptions}</div>
        </div>` : '';

    li.innerHTML = `
        <button onclick="toggleTask(${task.id}, this)" class="flex-shrink-0 w-5 h-5 border-2 border-black rounded-[3px] flex items-center justify-center cursor-pointer hover:bg-black/10 transition-colors"></button>
        <span class="flex-1 text-[15px]">${escapeHtml(task.text)}</span>
        ${promoteBtn}
        ${moveBtn}
        <button onclick="deleteTask(${task.id}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>
    `;
    list.appendChild(li);
}

async function toggleTask(id, btn) {
    const li = btn.closest('li');
    const span = li.querySelector('span');

    const res = await fetch(`/tasks/${id}/toggle`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    const data = await res.json();

    if (data.done) {
        btn.classList.add('bg-black');
        btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" fill="none"/></svg>`;
        span.classList.add('line-through', 'text-[#6B6B6B]');
        li.classList.add('opacity-50');
        const winsEl = document.getElementById('wins-count');
        winsEl.textContent = parseInt(winsEl.textContent) + 1;
        showToast();
    } else {
        btn.classList.remove('bg-black');
        btn.innerHTML = '';
        span.classList.remove('line-through', 'text-[#6B6B6B]');
        li.classList.remove('opacity-50');
        const winsEl = document.getElementById('wins-count');
        winsEl.textContent = Math.max(0, parseInt(winsEl.textContent) - 1);
    }

    updateCount(li.dataset.section);
}

async function promoteTask(id, btn) {
    const li = btn.closest('li');
    const section = li.dataset.section;

    const res = await fetch(`/tasks/${id}/promote`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (!res.ok) return;

    li.remove();
    updateCount(section);
    showToast('Moved to tomorrow 🚀');
}

async function deleteTask(id, btn) {
    const li = btn.closest('li');
    const section = li.dataset.section;

    const res = await fetch(`/tasks/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (!res.ok) return;

    li.remove();
    updateCount(section);
}

async function moveTask(id, targetSection, btn) {
    const dropdown = btn.closest('.move-dropdown');
    const li = dropdown.closest('li');
    const fromSection = li.dataset.section;
    dropdown.classList.add('hidden');

    const res = await fetch(`/tasks/${id}/move`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ section: targetSection })
    });

    if (!res.ok) {
        const data = await res.json();
        alert(data.error);
        return;
    }

    const text = li.querySelector('span').textContent;
    li.remove();
    appendTask({ id, text }, targetSection);
    removePlaceholder(targetSection);
    updateCount(fromSection);
    updateCount(targetSection);
    showToast('Moved ✓');
}

function toggleMoveMenu(e, btn) {
    e.stopPropagation();
    const dropdown = btn.nextElementSibling;
    document.querySelectorAll('.move-dropdown').forEach(d => {
        if (d !== dropdown) d.classList.add('hidden');
    });
    dropdown.classList.toggle('hidden');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.move-dropdown').forEach(d => d.classList.add('hidden'));
});

function updateCount(section) {
    const list = document.getElementById('task-list-' + section);
    const items = list.querySelectorAll('.task-item');
    const active = list.querySelectorAll('.task-item:not(.opacity-50)').length;
    const countEl = document.getElementById('count-' + section);
    const limits = { must: 3, should: 5 };
    countEl.textContent = active + (limits[section] ? '/' + limits[section] : '');
}

function removePlaceholder(section) {
    const list = document.getElementById('task-list-' + section);
    const placeholder = list.querySelector('.task-empty');
    if (placeholder) placeholder.remove();
}

function showResetModal() { document.getElementById('reset-modal').classList.remove('hidden'); }
function hideResetModal() { document.getElementById('reset-modal').classList.add('hidden'); }

async function confirmReset() {
    const res = await fetch('{{ route("day.reset") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (res.ok) window.location.reload();
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endsection
