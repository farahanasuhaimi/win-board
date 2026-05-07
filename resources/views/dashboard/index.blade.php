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
                <span class="text-2xl" id="commit-icon">{{ $commitDone ? '✅' : '🔒' }}</span>
                <span class="font-display font-bold text-xl {{ $commitDone ? 'line-through text-[#6B6B6B]' : '' }}" id="commit-text">{{ $commit?->text }}</span>
            </div>
            @if($commit && $commit->canUnlock() && !$commitDone)
                <button onclick="unlockCommit()" id="commit-edit-btn" class="btn text-sm" style="padding: 6px 14px;">Edit</button>
            @endif
        </div>
    </div>
    <div id="commit-form" class="{{ $commit && $commit->isLocked() ? 'hidden' : '' }}">
        <div class="flex gap-3">
            <input type="text"
                   id="commit-input"
                   class="input flex-1 text-lg font-medium"
                   placeholder="What is your ONE non-negotiable today?"
                   value="{{ $commit && !$commit->isLocked() ? $commit->text : '' }}"
                   maxlength="500">
            <button onclick="lockCommit()" class="btn whitespace-nowrap" style="padding: 10px 24px;">
                🔒 Lock it in
            </button>
        </div>
    </div>
</div>

{{-- Google Calendar strip (loaded async) --}}
<div id="calendar-strip" class="mb-6"></div>

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
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-display font-extrabold text-[13px] uppercase tracking-wider">{{ $meta['label'] }}</span>
                    <span class="bg-black text-white text-[11px] font-bold px-2 py-0.5 rounded-[3px] font-mono" id="count-{{ $key }}">
                        {{ $activeCount }}{{ $meta['limit'] ? '/'.$meta['limit'] : '' }}
                    </span>
                    @if($key === 'must')
                        @php $mustTotal = ($tasks['must'] ?? collect())->where('done', false)->sum('estimated_minutes'); @endphp
                        <span class="text-[11px] text-[#B0B0A8] font-mono" id="must-total">{{ $mustTotal > 0 ? ($mustTotal < 60 ? $mustTotal.'m' : floor($mustTotal/60).'h'.($mustTotal%60 > 0 ? ' '.($mustTotal%60).'m' : '')) : '' }}</span>
                    @endif
                </div>
            </div>
            @if($key === 'must')
            <div id="must-overload" class="{{ ($tasks['must'] ?? collect())->where('done', false)->sum('estimated_minutes') > 720 ? '' : 'hidden' }} text-[11px] font-bold text-[#E24B4A] border border-[#E24B4A] rounded-[4px] px-3 py-2 mb-3">
                That's over 12 hours — too much for one day.
            </div>
            @endif

            {{-- Task list --}}
            <ul class="space-y-2 mb-4 min-h-[60px]" id="task-list-{{ $key }}">
                @forelse($sectionTasks as $task)
                    @php
                        $estLabel = match((int)($task->estimated_minutes ?? 0)) {
                            10 => '10m', 30 => '30m', 60 => '1h', 120 => '2h', 360 => '6h',
                            default => null
                        };
                    @endphp
                    <li class="task-item flex items-center gap-3 group {{ $task->done ? 'opacity-50' : '' }}" data-id="{{ $task->id }}" data-section="{{ $key }}" data-estimate="{{ $task->estimated_minutes ?? 0 }}">
                        <button onclick="toggleTask({{ $task->id }}, this)" class="flex-shrink-0 w-5 h-5 border-2 border-black rounded-[3px] flex items-center justify-center cursor-pointer hover:bg-black/10 transition-colors {{ $task->done ? 'bg-black' : '' }}">
                            @if($task->done)
                                <svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" fill="none"/></svg>
                            @endif
                        </button>
                        <span class="flex-1 min-w-0 text-[15px] {{ $task->done ? 'line-through text-[#6B6B6B]' : '' }}">{{ $task->text }}</span>
                        @if($estLabel)
                            <span class="text-[11px] text-[#B0B0A8] font-mono shrink-0">{{ $estLabel }}</span>
                        @endif
                        @php
                            $daysLate = ($task->done || $task->section === 'park')
                                ? 0
                                : (int) $task->date->diffInDays(\Carbon\Carbon::today());
                            $urgencyEmoji = match(true) {
                                $daysLate >= 7 => '💀',
                                $daysLate >= 6 => '☠️',
                                $daysLate >= 5 => '🔥',
                                $daysLate >= 4 => '🚨',
                                $daysLate >= 3 => '🔴',
                                $daysLate >= 2 => '🟠',
                                $daysLate >= 1 => '⚠️',
                                default        => null,
                            };
                        @endphp
                        @if($urgencyEmoji)
                            <span class="shrink-0 w-5 h-5 flex items-center justify-center text-[14px] leading-none">{{ $urgencyEmoji }}</span>
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
                        @if($key === 'must')
                            <button onclick="deleteMustTask({{ $task->id }}, this)" class="opacity-30 hover:opacity-70 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>
                        @else
                            <button onclick="deleteTask({{ $task->id }}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>
                        @endif
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
                       class="input text-sm flex-1"
                       placeholder="Add task..."
                       onkeydown="if(event.key==='Enter') addTask('{{ $key }}')">
                <select id="est-{{ $key }}" class="input text-[12px]" style="padding: 8px 6px; width: auto; min-width: 0;">
                    <option value="">{{ $key === 'must' ? 'Time*' : '—' }}</option>
                    <option value="10">10m</option>
                    <option value="30">30m</option>
                    <option value="60">1h</option>
                    <option value="120">2h</option>
                    <option value="360">6h</option>
                </select>
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

{{-- Must task delete confirmation modal --}}
<div id="must-delete-modal" class="hidden fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4">
    <div class="card max-w-sm w-full" style="box-shadow: 6px 6px 0 #000;">
        <h3 class="font-display font-extrabold text-xl mb-2">Delete this Must task?</h3>
        <p class="text-[#6B6B6B] text-[14px] mb-5">It's a non-negotiable — want to park it instead of deleting?</p>
        <div class="flex flex-col gap-2">
            <button onclick="mustTaskPark()" class="btn" style="justify-content: center;">🅿️ Move to Parking Lot</button>
            <button onclick="mustTaskDelete()" class="btn btn-danger" style="justify-content: center;">Delete anyway</button>
            <button onclick="hideMustDeleteModal()" class="text-sm text-[#6B6B6B] hover:text-black font-medium text-center mt-1">Cancel</button>
        </div>
    </div>
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
const commitTaskId = {{ $commitTaskId ?? 'null' }};
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

    const body = { text };

    const res = await fetch('{{ route("commit.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(body)
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
    const input    = document.getElementById('add-' + section);
    const estSel   = document.getElementById('est-' + section);
    const errorEl  = document.getElementById('error-' + section);
    const text     = input.value.trim();
    const estimate = estSel ? estSel.value : '';
    if (!text) return;

    if (section === 'must' && !estimate) {
        errorEl.textContent = 'Set a time estimate for Must tasks.';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');

    const body = { text, section };
    if (estimate) body.estimated_minutes = parseInt(estimate);

    const res = await fetch('{{ route("tasks.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(body)
    });
    const data = await res.json();

    if (!res.ok) {
        errorEl.textContent = data.error;
        errorEl.classList.remove('hidden');
        return;
    }

    input.value = '';
    if (estSel) estSel.value = '';
    appendTask(data, section);
    updateCount(section);
    removePlaceholder(section);
    if (section === 'must') updateMustTotal();
}

function appendTask(task, section) {
    const list = document.getElementById('task-list-' + section);
    const li = document.createElement('li');
    li.className = 'task-item flex items-center gap-3 group';
    li.dataset.id       = task.id;
    li.dataset.section  = section;
    li.dataset.estimate = task.estimated_minutes || 0;

    const estHtml = formatEstimate(task.estimated_minutes)
        ? `<span class="text-[11px] text-[#B0B0A8] font-mono shrink-0">${formatEstimate(task.estimated_minutes)}</span>`
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
        <span class="flex-1 min-w-0 text-[15px]">${escapeHtml(task.text)}</span>
        ${estHtml}
        ${moveBtn}
        ${section === 'must'
            ? `<button onclick="deleteMustTask(${task.id}, this)" class="opacity-30 hover:opacity-70 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>`
            : `<button onclick="deleteTask(${task.id}, this)" class="opacity-100 md:opacity-0 md:group-hover:opacity-100 text-[#E24B4A] text-lg leading-none font-bold ml-1">×</button>`
        }
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
    if (li.dataset.section === 'must') updateMustTotal();

    if (commitTaskId && id === commitTaskId) {
        const icon    = document.getElementById('commit-icon');
        const text    = document.getElementById('commit-text');
        const editBtn = document.getElementById('commit-edit-btn');
        if (data.done) {
            if (icon) icon.textContent = '✅';
            if (text) text.classList.add('line-through', 'text-[#6B6B6B]');
            if (editBtn) editBtn.classList.add('hidden');
        } else {
            if (icon) icon.textContent = '🔒';
            if (text) text.classList.remove('line-through', 'text-[#6B6B6B]');
            if (editBtn) editBtn.classList.remove('hidden');
        }
    }
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
    if (section === 'must') updateMustTotal();
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

    const text     = li.querySelector('span').textContent;
    const estimate = parseInt(li.dataset.estimate || 0);
    li.remove();
    appendTask({ id, text, estimated_minutes: estimate }, targetSection);
    removePlaceholder(targetSection);
    updateCount(fromSection);
    updateCount(targetSection);
    if (fromSection === 'must' || targetSection === 'must') updateMustTotal();
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

let mustDeleteTaskId = null;
let mustDeleteBtn    = null;

function deleteMustTask(id, btn) {
    mustDeleteTaskId = id;
    mustDeleteBtn    = btn;
    document.getElementById('must-delete-modal').classList.remove('hidden');
}

function hideMustDeleteModal() {
    document.getElementById('must-delete-modal').classList.add('hidden');
    mustDeleteTaskId = null;
    mustDeleteBtn    = null;
}

async function mustTaskPark() {
    const id  = mustDeleteTaskId;
    const btn = mustDeleteBtn;
    hideMustDeleteModal();

    const li = btn.closest('li');
    const res = await fetch(`/tasks/${id}/move`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ section: 'park' })
    });

    if (!res.ok) { const d = await res.json(); alert(d.error); return; }

    const text     = li.querySelector('span').textContent;
    const estimate = parseInt(li.dataset.estimate || 0);
    li.remove();
    appendTask({ id, text, estimated_minutes: estimate }, 'park');
    removePlaceholder('park');
    updateCount('must');
    updateCount('park');
    updateMustTotal();
    showToast('Parked 🅿️');
}

async function mustTaskDelete() {
    const id  = mustDeleteTaskId;
    const btn = mustDeleteBtn;
    hideMustDeleteModal();
    await deleteTask(id, btn);
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

function formatEstimate(mins) {
    const map = { 10: '10m', 30: '30m', 60: '1h', 120: '2h', 360: '6h' };
    return map[parseInt(mins)] || null;
}

function formatMinutes(mins) {
    if (mins < 60) return mins + 'm';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return m > 0 ? `${h}h ${m}m` : `${h}h`;
}

function updateMustTotal() {
    const list = document.getElementById('task-list-must');
    if (!list) return;
    let total = 0;
    list.querySelectorAll('.task-item:not(.opacity-50)').forEach(li => {
        total += parseInt(li.dataset.estimate || 0);
    });
    const totalEl   = document.getElementById('must-total');
    const warningEl = document.getElementById('must-overload');
    if (totalEl)   totalEl.textContent = total > 0 ? formatMinutes(total) : '';
    if (warningEl) warningEl.classList.toggle('hidden', total <= 720);
}

// Load calendar strip async so dashboard renders immediately
(async function loadCalendar() {
    const strip = document.getElementById('calendar-strip');
    try {
        const res = await fetch('{{ route("calendar.events") }}');
        const { events, meeting_minutes } = await res.json();
        if (!events.length) return;

        const allDay = events.filter(e => e.all_day);
        const timed  = events.filter(e => !e.all_day);
        const mustTotal = Array.from(document.querySelectorAll('#task-list-must .task-item:not(.opacity-50)'))
            .reduce((s, li) => s + parseInt(li.dataset.estimate || 0), 0);
        const freeMinutes = Math.max(0, 480 - meeting_minutes);

        let meetingHtml = '';
        if (meeting_minutes > 0) {
            const mLabel = formatMinutes(meeting_minutes);
            let fitHtml = '';
            if (mustTotal > 0) {
                fitHtml = mustTotal <= freeMinutes
                    ? ' · <span class="text-[#23A094]">Must fits ✓</span>'
                    : ' · <span class="text-[#E24B4A]">Must won\'t fit ✗</span>';
            }
            meetingHtml = `<span class="text-[11px] font-mono text-[#6B6B6B]">${mLabel} in meetings${fitHtml}</span>`;
        }

        const allDayHtml = allDay.length
            ? `<div class="flex flex-wrap gap-2 mb-2">${allDay.map(e =>
                `<span class="text-[11px] font-medium bg-[#F4F4F0] border border-black px-2 py-0.5 rounded-[3px]">${escapeHtml(e.title)}</span>`
              ).join('')}</div>`
            : '';

        const timedHtml = timed.length
            ? `<div class="space-y-1.5">${timed.map(e =>
                `<div class="flex items-center gap-3">
                    <span class="text-[11px] font-mono text-[#6B6B6B] shrink-0 w-24">${e.start_time} – ${e.end_time}</span>
                    <span class="text-[13px] font-medium truncate">${escapeHtml(e.title)}</span>
                    ${e.duration ? `<span class="text-[11px] font-mono text-[#B0B0A8] shrink-0 ml-auto">${formatMinutes(e.duration)}</span>` : ''}
                </div>`
              ).join('')}</div>`
            : '';

        strip.innerHTML = `
            <div class="card" style="box-shadow: var(--shadow-hard-sm);">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#6B6B6B]">Today's Schedule</span>
                    ${meetingHtml}
                </div>
                ${allDayHtml}
                ${timedHtml}
            </div>`;
    } catch (e) {
        // Calendar unavailable — fail silently
    }
})();
</script>
@endsection
