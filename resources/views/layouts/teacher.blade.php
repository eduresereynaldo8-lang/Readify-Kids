{{-- Role entry point; navigation and responsive behavior live in one shared shell. --}}
@php $teacher = auth()->user()->teacher; @endphp
@extends('layouts.dashboard')
