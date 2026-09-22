{{-- Role entry point; navigation and responsive behavior live in one shared shell. --}}
@php $student = auth()->user()->student; @endphp
@extends('layouts.dashboard')
